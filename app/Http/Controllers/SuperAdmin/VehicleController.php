<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreVehicleRequest;
use App\Http\Resources\SuperAdmin\MaintenanceHistoryResource;
use App\Http\Resources\SuperAdmin\VehicleDatatableResource;
use App\Http\Resources\SuperAdmin\VehicleResource;
use App\Models\Franchise;
use App\Models\Branch;
use App\Models\Status;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

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
        $vehicle->loadMissing(['status:id,name', 'vehicleType:id,name', 'franchise:id,name', 'branch:id,name']);

        return new VehicleResource($vehicle);
    }

    public function create(): Response
    {
        // Fetch franchises with their "Active" vehicle types only
        $franchises = Franchise::select('id', 'name')
            ->with(['vehicleTypes' => function ($query) {
                $query->select('vehicle_types.id', 'vehicle_types.name')
                    ->wherePivot('status_id', function ($q) {
                        $q->select('id')->from('statuses')->where('name', 'active');
                    });
            }])
            ->get();

        // Fetch branches with their franchise relationship
        $branches = Branch::select('id', 'franchise_id', 'name')
            ->with('franchise:id,name')
            ->get();

        return Inertia::render('super-admin/fleet/VehicleCreate', [
            'franchises' => $franchises,
            'branches' => $branches,
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

    public function store(StoreVehicleRequest $request)
    {
        $availableStatusId = Status::where('name', 'available')->firstOrFail()->id;

        // 1. Create the vehicle instance first
        $vehicle = new Vehicle([
            'status_id' => $availableStatusId,
            'vehicle_type_id' => $request->vehicle_type_id,
            'franchise_id' => $request->franchise_id,
            'branch_id' => $request->branch_id,
            'plate_number' => $request->plate_number,
            'vin' => $request->vin,
            'capacity' => $request->capacity,
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
