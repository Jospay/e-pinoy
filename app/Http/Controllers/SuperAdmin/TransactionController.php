<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\TransactionDatatableResource;
use App\Http\Resources\SuperAdmin\TransactionResource;
use App\Models\Franchise;
use App\Models\Revenue;
use App\Models\Expense;
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
            'type' => ['sometimes', 'string', Rule::in(['revenue', 'expense'])],
            'franchise' => ['sometimes', 'nullable', 'array'], 
            'driver' => ['sometimes', 'nullable', 'array'],
            'service' => ['sometimes', 'string', Rule::in(['Trips', 'Boundary'])],
        ]);

        // 2. Set defaults
        $filters = [
            'type' => $validated['type'] ?? 'revenue',
            'franchise' => $validated['franchise'] ?? [],
            'driver' => $validated['driver'] ?? [],
            'service' => $validated['service'] ?? 'Trips',
        ];

        // 3. Build and execute query
        $query = $this->buildBaseQuery($filters)->get();

        // 4. Fetch Context-Aware Drivers List
        $driversList = $this->getContextualDrivers($filters);

        // 5. Return all data to Inertia
        return Inertia::render('super-admin/finance/TransactionIndex', [
            'transactions' => TransactionDatatableResource::collection($query),
            'franchises' => fn () => Franchise::select('id', 'name')->get(),
            'drivers' => fn () => $driversList,
            'filters' => $filters,
        ]);
        
    }

    /**
     * Creates the base query with all "WHERE" conditions.
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $isExpense = $filters['type'] === 'expense';
        $query = $isExpense ? Expense::query() : Revenue::query();

        // Conditional Eager Loading
        $relations = ['status:id,name'];
        if (!$isExpense) {
            $relations[] = 'driver.user:id,username';
            $query->where('service_type', $filters['service']); // Only for revenue
        }
        $query->with($relations);

        // Filter by specific driver if selected
        $query->when(!empty($filters['driver']), function ($q) use ($filters) {
            $q->whereIn('driver_id', $filters['driver']);
        });

        // Filter by specific franchise if selected
        $query->whereNotNull('franchise_id')
            ->when(!empty($filters['franchise']), fn ($q) => $q->whereIn('franchise_id', $filters['franchise']));
        $query->with('franchise:id,name');

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

        if (!empty($filters['franchise'])) {
            // Get drivers strictly belonging to this franchise
            $query->whereHas('franchises', function ($q) use ($filters) {
                $q->whereIn('franchises.id', $filters['franchise']);
            });
        } else {
            // Get ALL drivers that belong to ANY franchise
            $query->has('franchises');
        }

        return $query->orderBy('users.username')->get();
    }

    public function show(Request $request, $id)
    {
        $type = $request->query('type', 'revenue');

        $model = $type === 'expense' 
        ? Expense::with(['status', 'franchise:id,name', 'paymentOption', 'maintenance.inventory', 'maintenance.vehicle'])
        : Revenue::with(['status', 'driver.user:id,username', 'franchise:id,name', 'paymentOption:id,name']);

        $transaction = $model->findOrFail($id);

        return new TransactionResource($transaction);
    }
}
