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
use App\Models\VehicleType;
use App\Models\Branch;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BoundaryContractController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'tab' => ['sometimes', 'string', 'exists:vehicle_types,name'],
            'type' => ['sometimes', 'string', Rule::in(['franchise', 'branch'])],
            'franchises' => ['sometimes', 'nullable', 'array'],
            'branches' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'pending', 'inactive'])],
        ]);

        // 2. Set defaults
        $filters = [
            'tab' => $validated['tab'] ?? 'taxi',
            'type' => $validated['type'] ?? 'franchise',
            'franchises' => $validated['franchises'] ?? [],
            'branches' => $validated['branches'] ?? [],
            'status' => $validated['status'] ?? 'active',
        ];

        // 3. Build and execute query
        $contracts = $this->buildBaseQuery($filters)->get();
        // Pass status map to the resource class statically — 1 query, no N+1
        BoundaryContractDatatableResource::withStatusMap(
            Status::all()->keyBy('id')
        );

        $activeStatusId = Status::where('name', 'active')->value('id');

        $franchiseList = Franchise::select('id', 'name')
            ->whereHas('vehicleTypes', function ($q) use ($activeStatusId, $filters) {
                $q->where('vehicle_types.name', $filters['tab'])
                ->where('franchise_vehicle_type.status_id', $activeStatusId);
            })
            ->get();
            
        $branchList = Branch::select('id', 'name', 'franchise_id')
            ->whereHas('franchise.vehicleTypes', function ($q) use ($activeStatusId, $filters) {
                $q->where('vehicle_types.name', $filters['tab'])
                ->where('franchise_vehicle_type.status_id', $activeStatusId);
            })->when(!empty($filters['franchises']), function ($q) use ($filters) {
                $q->whereIn('franchise_id', $filters['franchises']);
            })
            ->get();

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/fleet/BoundaryContractIndex', [
            'contracts' => BoundaryContractDatatableResource::collection($contracts),
            'franchises' => $franchiseList,
            'branches' => $branchList,
            'vehicleTypes' => fn () => VehicleType::select('id', 'name')->orderBy('id', 'asc')->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * Creates the base query with all "WHERE" conditions.
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $pivotStatusId = Status::where('name', $filters['status'])->value('id');
        
        $query = BoundaryContract::with([
            'vehicleTypes' => function ($q) use ($filters, $pivotStatusId) {
                    $q->where('vehicle_types.name', $filters['tab'])
                    ->where('boundary_contract_vehicle_type.status_id', $pivotStatusId)
                    ->withPivot('amount', 'status_id');
                },
            'driver.user:id,username',
        ])->whereHas('vehicleTypes', function ($q) use ($filters, $pivotStatusId) {
            $q->where('vehicle_types.name', $filters['tab'])
            ->where('boundary_contract_vehicle_type.status_id', $pivotStatusId);
        });

        if ($filters['type'] === 'franchise') {
            $query->whereNotNull('franchise_id')
                ->when(!empty($filters['franchises']), fn ($q) => $q->whereIn('franchise_id', $filters['franchises']))
                ->with('franchise:id,name');
        } else {
            $query->whereNotNull('branch_id')
                ->whereHas('branch.franchise.vehicleTypes', function ($q) use ($filters) {
                    $q->where('vehicle_types.name', $filters['tab']);
                })
                ->when(!empty($filters['branches']), fn ($q) => $q->whereIn('branch_id', $filters['branches']))
                ->with(['branch:id,name,franchise_id', 'branch.franchise:id,name']);
        }

        return $query;
    }

    public function show(BoundaryContract $contract)
    {
        // Load relationships and return as JSON
        $contract->loadMissing([
        'driver.user:id,username,name,email,phone',
        'franchise:id,name,email,phone', 
        'branch:id,name,email,phone,franchise_id',
        'branch.franchise:id,name',
        'vehicleTypes' => function ($query) {
                $query->withPivot('amount', 'status_id');
            },
        ]);

        return new BoundaryContractResource($contract);
    }

    public function create(): Response
    {
        $franchises = Franchise::select('id', 'name')->get();
        $branches   = Branch::select('id', 'name', 'franchise_id')
                            ->with('franchise:id,name')
                            ->get();

        return Inertia::render('super-admin/fleet/BoundaryContractCreate', [
            'franchises' => $franchises,
            'branches'   => $branches,
        ]);
    }

    public function getVehicleTypes(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in(['franchise', 'branch'])],
            'id'   => ['required', 'integer'],
        ]);

        $activeStatusId = Status::where('name', 'active')->value('id');

        // Resolve the franchise ID — branch uses its parent franchise
        $franchiseId = $request->type === 'franchise'
            ? $request->id
            : Branch::where('id', $request->id)->value('franchise_id');

        if (! $franchiseId) {
            return response()->json(['vehicleTypes' => []]);
        }

        $vehicleTypes = VehicleType::select('id', 'name')
            ->whereHas('franchises', function ($q) use ($franchiseId, $activeStatusId) {
                $q->where('franchises.id', $franchiseId)
                ->where('franchise_vehicle_type.status_id', $activeStatusId);
            })
            ->orderBy('id')
            ->get();

        return response()->json(['vehicleTypes' => $vehicleTypes]);
    }

    public function getDrivers(Request $request): JsonResponse
    {
        $request->validate([
            'type'            => ['required', 'string', Rule::in(['franchise', 'branch'])],
            'id'              => ['required', 'integer'],
            'vehicle_type_id' => ['required', 'integer'],
        ]);

        $activeStatusId = Status::where('name', 'active')->value('id');

        if (! $activeStatusId) {
            return response()->json(['drivers' => []]);
        }

        $drivers = UserDriver::with('user:id,name')
            ->where('status_id', $activeStatusId)
            // Must have the selected vehicle type
            ->whereHas('vehicleTypes', function ($q) use ($request) {
                $q->where('vehicle_types.id', $request->vehicle_type_id);
            })
            // Must belong to the selected franchise or branch
            ->when($request->type === 'franchise', function ($q) use ($request) {
                $q->whereHas('franchises', fn ($q) =>
                    $q->where('franchises.id', $request->id)
                );
            })
            ->when($request->type === 'branch', function ($q) use ($request) {
                $q->whereHas('branches', fn ($q) =>
                    $q->where('branches.id', $request->id)
                );
            })
            // Must NOT have an active boundary contract (status lives on the pivot)
            ->whereDoesntHave('boundaryContracts', function ($q) use ($activeStatusId) {
                $q->whereHas('vehicleTypes', fn ($q) =>
                    $q->where('boundary_contract_vehicle_type.status_id', $activeStatusId)
                );
            })
            ->get()
            ->map(fn ($d) => [
                'id'   => $d->id,
                'name' => $d->user->name,
            ]);

        return response()->json(['drivers' => $drivers]);
    }

    public function store(StoreBoundaryContractRequest $request)
    {
        DB::transaction(function () use ($request) {

            $activeStatusId = Status::where('name', 'active')->firstOrFail()->id;

            $contract = BoundaryContract::create([
                'franchise_id'   => $request->franchise_id,
                'branch_id'      => $request->branch_id,
                'driver_id'      => $request->driver_id,
                'name'           => $request->name,
                'currency'       => 'PHP',
                'coverage_area'  => $request->coverage_area,
                'contract_terms' => $request->contract_terms,
                'renewal_terms'  => $request->renewal_terms,
                'start_date'     => $request->start_date,
                'end_date'       => $request->end_date,
            ]);

            // Attach vehicle type with pending status to the pivot
            $contract->vehicleTypes()->attach($request->vehicle_type_id, [
                'status_id' => $activeStatusId,
                'amount'    => $request->amount,
            ]);
        });

        return redirect(route('super-admin.boundaryContract.index'));
    }
}
