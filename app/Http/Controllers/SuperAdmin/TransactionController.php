<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\TransactionDatatableResource;
use App\Http\Resources\SuperAdmin\TransactionResource;
use App\Models\Franchise;
use App\Models\Revenue;
use App\Models\Expense;
use App\Models\Status;
use App\Models\Branch;
use App\Models\VehicleType;
use App\Models\UserDriver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'tab' => ['sometimes', 'string', 'exists:vehicle_types,name'],
            'type' => ['sometimes', 'string', Rule::in(['franchise', 'branch'])],
            'franchises' => ['sometimes', 'nullable', 'array'], 
            'branches' => ['sometimes', 'nullable', 'array'],
            'driver' => ['sometimes', 'nullable', 'array'],
            'category' => ['sometimes', 'string', Rule::in(['expense', 'revenue'])],
        ]);

        // 2. Set defaults
        $filters = [
            'tab' => $validated['tab'] ?? 'taxi',
            'type' => $validated['type'] ?? 'franchise',
            'franchises' => $validated['franchises'] ?? [],
            'branches' => $validated['branches'] ?? [],
            'driver' => $validated['driver'] ?? [],
            'category' => $validated['category'] ?? 'revenue',
        ];

        // 3. Build and execute query
        $query = $this->buildBaseQuery($filters)->get();

        // 4. Fetch Context-Aware Drivers List
        $driversList = $this->getContextualDrivers($filters);

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

        // 5. Return all data to Inertia
        return Inertia::render('super-admin/finance/TransactionIndex', [
            'transactions' => TransactionDatatableResource::collection($query),
            'franchises' => $franchiseList,
            'branches' => $branchList,
            'vehicleTypes' => fn () => VehicleType::select('id', 'name')->orderBy('id', 'asc')->get(),
            'drivers' => fn () => $driversList,
            'filters' => $filters,
        ]);
        
    }

    /**
     * Creates the base query with all "WHERE" conditions.
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $isExpense = $filters['category'] === 'expense';
        $query = $isExpense ? Expense::query() : Revenue::query();

        // Conditional Eager Loading
        $relations = ['status:id,name'];
        if ($isExpense) {
            $query->whereHas('maintenance.vehicle.vehicleType', function ($q) use ($filters) {
                $q->where('name', $filters['tab']);
            });

            $relations[] = 'maintenance.vehicle.vehicleType:id,name';
        } else {
            $query->whereHas('vehicleType', function ($q) use ($filters) {
                $q->where('name', $filters['tab']);
            });
            $query->where('service_type', 'Trips');
            
            $relations[] = 'driver.user:id,username';
            $relations[] = 'vehicleType:id,name';
        }
        $query->with($relations);

        // Filter by specific driver if selected
        $query->when(!empty($filters['driver']), function ($q) use ($filters) {
            $q->whereIn('driver_id', $filters['driver']);
        });

        // Filter by specific franchise/branch if selected
        if ($filters['type'] === 'franchise') {
            $query->whereNotNull('franchise_id')
                ->when(!empty($filters['franchise']), fn ($q) => $q->whereIn('franchise_id', $filters['franchise']));
            $query->with('franchise:id,name');
        } else {
            $query->whereNotNull('branch_id')
                ->when(!empty($filters['branch']), fn ($q) => $q->whereIn('branch_id', $filters['branch']));
            $query->with('branch:id,name');
        }

        return $query;
    }

    /**
     * Efficiently fetches drivers based on the current view context
     */
    private function getContextualDrivers(array $filters)
    {
        // Start with UserDriver and join the base User table to get username
        $query = UserDriver::query()
            ->join('users', 'user_drivers.id', '=', 'users.id')
            ->select('user_drivers.id', 'users.username');

        if ($filters['type'] === 'franchise') {
            if (!empty($filters['franchise'])) {
                // Get drivers strictly belonging to this franchise
                $query->whereHas('franchises', function ($q) use ($filters) {
                    $q->whereIn('franchises.id', $filters['franchise']);
                });
            } else {
                // Get ALL drivers that belong to ANY franchise
                $query->has('franchises');
            }
        } elseif ($filters['type'] === 'branch') {
            if (!empty($filters['branch'])) {
                // Get drivers strictly belonging to this branch
                $query->whereHas('branches', function ($q) use ($filters) {
                    $q->whereIn('branches.id', $filters['branch']);
                });
            } else {
                // Get ALL drivers that belong to ANY branch
                $query->has('branches');
            }
        }

        return $query->orderBy('users.username')->get();
    }

    public function show(Request $request, $id)
    {
        $category = $request->query('category', 'revenue');

        $model = $category === 'expense' 
        ? Expense::with(['status', 'franchise:id,name', 'branch:id,name', 'paymentOption:id,name', 'maintenance.inventory', 'maintenance.vehicle', 'maintenance.vehicle.vehicleType:id,name'])
        : Revenue::with(['status', 'driver.user:id,username', 'franchise:id,name', 'branch:id,name', 'paymentOption:id,name', 'vehicleType:id,name']);

        $transaction = $model->findOrFail($id);

        return new TransactionResource($transaction);
    }
}
