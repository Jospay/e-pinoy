<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SuperAdmin\BoundaryContractDatatableResource;
use App\Http\Resources\SuperAdmin\BoundaryContractResource;
use App\Http\Requests\SuperAdmin\StoreBoundaryContractRequest;
use App\Models\Vehicle;
use App\Models\BoundaryContract;
use App\Models\Franchise;
use App\Models\UserDriver;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BoundaryContractController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'franchise' => ['sometimes', 'nullable', 'array'], 
            'status' => ['sometimes', 'string', Rule::in(['active', 'pending', 'terminated', 'expired'])],
        ]);

        // 2. Set defaults
        $filters = [
            'franchise' => $validated['franchise'] ?? [],
            'status' => $validated['status'] ?? 'active',
        ];

        // 3. Build and execute query
        $query = $this->buildBaseQuery($filters);
        $contracts = $query->get();

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/fleet/BoundaryContractIndex', [
            'contracts' => BoundaryContractDatatableResource::collection($contracts),
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
        $query = BoundaryContract::with([
            'driver.user:id,username',
            'status:id,name',
        ])->whereHas('status', fn ($q) => $q->where('name', $filters['status']));

        $query->whereNotNull('franchise_id')
            ->when(!empty($filters['franchise']), fn ($q) => $q->whereIn('franchise_id', $filters['franchise']))
            ->with('franchise:id,name');

        return $query;
    }

    public function show(BoundaryContract $contract)
    {
        // Load relationships and return as JSON
        $contract->loadMissing([
            'driver.user:id,username,name,email,phone',
            'franchise:id,name,email,phone',
            'status:id,name'
        ]);

        return new BoundaryContractResource($contract);
    }

    public function create(): Response
    {
        return Inertia::render('super-admin/fleet/BoundaryContractCreate', [
            'franchises' => fn () => Franchise::select('id', 'name')->get(),
        ]);
    }

    public function getContractResources(Request $request)
    {
        $request->validate([
            'id'   => ['required', 'integer'],
        ]);
        $entityId = $request->id;

        // 1. Get ID of 'active' and 'available' status
        $activeStatusId = Status::where('name', 'active')->value('id');
        $availableStatusId = Status::where('name', 'available')->value('id');

        if (!$activeStatusId) {
            return response()->json(['drivers' => []]);
        }

        // 2. Query Drivers
        $drivers = UserDriver::with('user:id,name')
            ->where('status_id', $activeStatusId) // Driver must be active
            // Check pivot/relationship for franchise ownership
            ->whereHas('franchises', function ($q) use ($entityId) {
                $q->where('franchises.id', $entityId);
            })
            // Check availability (No active contract)
            ->whereDoesntHave('boundaryContracts', function ($q) use ($activeStatusId) {
                $q->where('status_id', $activeStatusId);
            })
            ->get()
            ->map(fn($d) => ['id' => $d->user->id, 'name' => $d->user->name]);

        // 3. Query Vehicles
        $vehicles = Vehicle::query()
            ->select('id', 'plate_number', 'brand', 'model')
            ->where('status_id', $availableStatusId) // Vehicle itself must be available
            // Check ownership
            ->where('franchise_id', $entityId)
            // Check availability (No active contract)
            ->whereDoesntHave('boundaryContracts', function ($q) use ($activeStatusId) {
                $q->where('status_id', $activeStatusId);
            })
            ->get()
            ->map(fn($v) => [
                'id' => $v->id, 
                'name' => "{$v->plate_number} - {$v->brand} {$v->model}" 
            ]);

        return response()->json([
            'drivers' => $drivers,
            'vehicles' => $vehicles
        ]);
    }

    public function store(StoreBoundaryContractRequest $request)
    {
        DB::transaction(function () use ($request) {

            $pendingStatusId = Status::where('name', 'pending')->firstOrFail()->id;

            BoundaryContract::create([
                'status_id' => $pendingStatusId,
                'franchise_id' => $request->franchise_id,
                'driver_id' => $request->driver_id,
                'vehicle_id' => $request->vehicle_id,
                'name' => $request->name,
                'amount' => $request->amount,
                'currency' => 'PHP',
                'coverage_area' => $request->coverage_area,
                'contract_terms' => $request->contract_terms,
                'renewal_terms' => $request->renewal_terms,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            // Update vehicle driver_id
            Vehicle::where('id', $request->vehicle_id)->update(['driver_id' => $request->driver_id]);
        });

        return redirect(route('super-admin.boundaryContract.index'));
    }
}
