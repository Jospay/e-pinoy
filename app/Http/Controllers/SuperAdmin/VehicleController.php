<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreVehicleRequest;
use App\Http\Resources\SuperAdmin\MaintenanceHistoryResource;
use App\Http\Resources\SuperAdmin\VehicleDatatableResource;
use App\Http\Resources\SuperAdmin\VehicleResource;
use App\Models\Franchise;
use App\Models\Status;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Storage;

class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'franchise' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'available', 'maintenance'])],
        ]);

        // 2. Set defaults
        $filters = [
            'franchise' => $validated['franchise'] ?? [],
            'status' => $validated['status'] ?? 'active',
        ];

        // 3. Build and execute query
        $query = $this->buildBaseQuery($filters);
        $vehicles = $query->get();

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/fleet/VehicleIndex', [
            'vehicles' => VehicleDatatableResource::collection($vehicles),
            'franchises' => fn () => Franchise::select('id', 'name')->get(),
            'filters' => [
                'franchise' => $filters['franchise'],
                'status' => $filters['status'],
            ],
        ]);
    }

    /**
     * Creates the base query with all "WHERE" conditions.
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $query = Vehicle::with([
            'status:id,name',
        ])->whereHas('status', fn ($q) => $q->where('name', $filters['status']));

        $query->whereNotNull('franchise_id')
            ->when(! empty($filters['franchise']), fn ($q) => $q->whereIn('franchise_id', $filters['franchise']))
            ->with('franchise:id,name');

        return $query;
    }

    public function show(Vehicle $vehicle)
    {
        // Load relationships and return as JSON
        $vehicle->loadMissing(['status:id,name']);

        return new VehicleResource($vehicle);
    }

    public function create(): Response
    {
        return Inertia::render('super-admin/fleet/VehicleCreate', [
            'franchises' => fn () => Franchise::select('id', 'name')->get(),
        ]);
    }

    public function changeStatus(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'status' => ['required', 'string', Rule::in(['active', 'available', 'maintenance'])],
        ]);

        $status = Status::where('name', $request->status)->firstOrFail();
        $vehicle->status_id = $status->id;
        $vehicle->save();

        return back();
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'franchise_id' => ['required', 'exists:franchises,id'],
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('vehicles')->ignore($vehicle->id)],
            'vin' => ['required', 'string', 'max:50', Rule::unique('vehicles')->ignore($vehicle->id)],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer'],
            'color' => ['required', 'string', 'max:50'],
            'or_cr' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        // Update text fields (this excludes the file from the array automatically if not provided)
        $vehicle->fill($request->except('or_cr'));

        if ($request->hasFile('or_cr')) {
            // 1. Delete old file from storage if it exists
            if ($vehicle->or_cr) {
                Storage::disk('public')->delete('vehicle_documents/'.$vehicle->or_cr);
            }

            // 2. Handle New File Upload
            $file = $request->file('or_cr');

            // Consistent naming: timestamp + cleaned plate number
            $filename = time().'_or_cr_'.str_replace(' ', '_', $request->plate_number).'.'.$file->getClientOriginalExtension();

            $file->storeAs('vehicle_documents', $filename, 'public');

            // 3. Update the database column
            $vehicle->or_cr = $filename;
        }

        $vehicle->save();

        // Use 'back' so the Inertia modal refreshes its data automatically
        return back()->with('success', 'Vehicle updated successfully');
    }

    public function store(StoreVehicleRequest $request)
    {
        $availableStatusId = Status::where('name', 'available')->firstOrFail()->id;

        // 1. Create the vehicle instance first
        $vehicle = new Vehicle([
            'status_id' => $availableStatusId,
            'franchise_id' => $request->franchise_id,
            'plate_number' => $request->plate_number,
            'vin' => $request->vin,
            'brand' => $request->brand,
            'model' => $request->model,
            'color' => $request->color,
            'year' => $request->year,
            'or_cr' => $request->or_cr,
        ]);

        // 2. Handle File Upload using your specific naming logic
        if ($request->hasFile('or_cr')) {
            $file = $request->file('or_cr');

            // Naming: time() + plate_number for uniqueness
            $filename = time().'_or_cr_'.str_replace(' ', '_', $request->plate_number).'.'.$file->getClientOriginalExtension();

            // Store in storage/app/public/vehicle_documents
            $file->storeAs('vehicle_documents', $filename, 'public');

            // Save filename to database column
            $vehicle->or_cr = $filename;
        }

        $vehicle->save();

        return redirect(route('super-admin.vehicle.index'));
    }

    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {

            $vehicle = Vehicle::findOrFail($id);

            $vehicle->maintenances()->each(function ($maintenance) {
                $maintenance->expenses()->delete();
            });

            $vehicle->maintenances()->delete();

            $vehicle->load('boundaryContracts.revenues');

            foreach ($vehicle->boundaryContracts as $contract) {
                $contract->revenues()->delete();
            }

            $vehicle->boundaryContracts()->delete();

            $vehicle->routes()->delete();

            $vehicle->delete();
        });

        return back()->with('success', 'Vehicle deleted successfully.');
    }

    public function maintenanceHistory(Vehicle $vehicle)
    {
        // Eager load maintenance with its related inventory
        $vehicle->loadMissing(['maintenances' => function ($query) {
            $query->orderBy('maintenance_date', 'desc')
                ->with('inventory:id,name,category,specification'); // eager load inventory
        }]);

        return MaintenanceHistoryResource::collection($vehicle->maintenances);
    }
}
