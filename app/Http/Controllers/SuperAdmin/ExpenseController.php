<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\ExpenseDatatableResource;
use App\Http\Resources\SuperAdmin\ExpenseShowResource;
use App\Models\Franchise;
use App\Models\Expense;
use App\Models\Status;
use App\Models\Branch;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\ExpenseExport;
use Maatwebsite\Excel\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'tab' => ['sometimes', 'string', 'exists:vehicle_types,name'],
            'type' => ['sometimes', 'string', Rule::in(['franchise', 'branch'])],
            'franchises' => ['sometimes', 'nullable', 'array'], 
            'branches' => ['sometimes', 'nullable', 'array'],
            'period' => ['sometimes', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
        ]);

        // 2. Set defaults
        $filters = [
            'tab' => $validated['tab'] ?? 'taxi',
            'type' => $validated['type'] ?? 'franchise',
            'franchises' => $validated['franchises'] ?? [],
            'branches' => $validated['branches'] ?? [],
            'period' => $validated['period'] ?? 'daily',
        ];

        // 3. Build and execute query
        $query = $this->buildBaseQuery($filters);
        $expenses = $this->applyPeriodGrouping($query, $filters['period'], $filters['type']);

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
        return Inertia::render('super-admin/finance/ExpenseIndex', [
            'expenses' => ExpenseDatatableResource::collection($expenses),
            'franchises' => $franchiseList,
            'branches' => $branchList,
            'vehicleTypes' => fn () => VehicleType::select('id', 'name')->orderBy('id', 'asc')->get(),
            'filters' => $filters,
        ]);
        
    }

    public function show(Request $request): Response
    {
        $validated = $request->validate([
            'start'     => ['required', 'date'],
            'end'       => ['required', 'date'],
            'label'     => ['required', 'string'],
            'tab'       => ['required', 'string', 'exists:vehicle_types,name'],
            'type'      => ['required', 'string', Rule::in(['franchise', 'branch'])],
            'franchise' => ['nullable'],
            'branch'    => ['nullable'],
        ]);

        // 1. Determine which ID we are filtering for
        $id = $validated['type'] === 'franchise' ? $validated['franchise'] : $validated['branch'];
        
        // 2. Normalize filters for the buildBaseQuery
        $filters = [
            'tab'       => $validated['tab'] ?? 'taxi',
            'type'      => $validated['type'] ?? 'franchise',
            'franchise' => $validated['type'] === 'franchise' ? [$id] : [],
            'branch'    => $validated['type'] === 'branch' ? [$id] : [],
        ];

        // 3. Fetch specific Target Name for header
        $targetName = 'N/A';
        if ($validated['type'] === 'franchise' && $id) {
            $targetName = Franchise::find($id)?->name;
        } elseif ($validated['type'] === 'branch' && $id) {
            $targetName = Branch::find($id)?->name;
        }
       
        // 4. Build Query
        $query = $this->buildBaseQuery($filters);

        // Filter by the exact date range from the clicked row
        $query->whereBetween(DB::raw('DATE(payment_date)'), [
            $validated['start'], 
            $validated['end']
        ]);

        $details = $query->with([
                'maintenance.vehicle:id,plate_number', 
                'maintenance.inventory:id,name'])
            ->orderBy('payment_date', 'desc')
            ->get();

        return Inertia::render('super-admin/finance/ExpenseShow', [
            'details'     => ExpenseShowResource::collection($details),
            'periodLabel' => $validated['label'],
            'targetName'  => $targetName,
            'targetTab'  =>  $validated['tab'],
            'totalSum'    => $details->sum('amount'),
            'filters'     => $filters,
        ]);
    }

    /**
     * Creates the base query with all "WHERE" conditions.
     */
    private function buildBaseQuery(array $filters, ?int $year = null, ?array $months = null): Builder
    {
        $query = Expense::query()
            ->whereHas('status', fn ($q) => $q->where('name', 'paid'))
            ->whereNotNull('payment_date')
            ->whereHas('maintenance.vehicle.vehicleType', fn ($q) => $q->where('name', $filters['tab']));

            // --- Apply date constraints for export only ---
            if ($year) {
                $query->whereYear('payment_date', $year);
            }
            if (! empty($months)) {
                $query->whereIn(DB::raw('MONTH(payment_date)'), $months);
            }

        if ($filters['type'] === 'franchise') {
            $query->whereNotNull('franchise_id')
                ->when(!empty($filters['franchises']), fn ($q) => $q->whereIn('franchise_id', $filters['franchises']));
        } elseif ($filters['type'] === 'branch') {
            $query->whereNotNull('branch_id')
                ->when(!empty($filters['branches']), fn ($q) => $q->whereIn('branch_id', $filters['branches']));
        }

        return $query;
    }

    /**
     * Applies the SELECT and GROUP BY logic based on the period.
     */
    private function applyPeriodGrouping(Builder $query, string $period, string $type)
    {
        // Base selections for ALL periods (now including daily)
        $query->selectRaw('
            SUM(expenses.amount) as total_amount
        ');

        // Apply franchise grouping
        if ($type === 'franchise') {
            $query->join('franchises', 'expenses.franchise_id', '=', 'franchises.id')
                ->addSelect('franchises.id as franchise_id', 'franchises.name as franchise_name')
                ->groupBy('franchises.id', 'franchises.name');
        } elseif ($type === 'branch') {
            $query->join('branches', 'expenses.branch_id', '=', 'branches.id')
                ->addSelect('branches.id as branch_id', 'branches.name as branch_name')
                ->groupBy('branches.id', 'branches.name');
        }

        // Apply period-specific grouping
        if ($period === 'daily') {
            $query->addSelect(DB::raw('DATE(expenses.payment_date) as payment_date'))
                ->groupBy(DB::raw('DATE(expenses.payment_date)'))
                ->orderBy('payment_date', 'desc');
        } elseif ($period === 'weekly') {
            $query->addSelect(DB::raw('MIN(expenses.payment_date) as week_start, MAX(expenses.payment_date) as week_end'))
                ->groupByRaw('YEAR(expenses.payment_date), WEEK(expenses.payment_date, 1)')
                ->orderBy('week_start', 'desc');
        } elseif ($period === 'monthly') {
            $query->addSelect(DB::raw('DATE_FORMAT(expenses.payment_date, "%M %Y") as month_name, MIN(expenses.payment_date) as month_sort'))
                ->groupBy('month_name')
                ->orderBy('month_sort', 'desc');
        }

        return $query->get();
    }

    /**
     * Handles the export process.
     */
    public function exportIndex(Request $request)
    {
        // 1. Validate all inputs
        $validated = $request->validate([
            'tab' => ['required', 'string', 'exists:vehicle_types,name'],
            'type' => ['required', 'string', Rule::in(['franchise', 'branch'])],
            'franchises' => ['sometimes', 'nullable', 'array'], 
            'branches' => ['sometimes', 'nullable', 'array'], 
            'period' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'export' => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'months' => ['required', 'array', 'min:1'],
            'months.*' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $filters = [
            'tab' => $validated['tab'] ?? 'taxi',
            'type' => $validated['type'] ?? 'franchise',
            'franchises' => $validated['franchises'] ?? [],
            'branches' => $validated['branches'] ?? [],
            'period' => $validated['period'],
            'export' => $validated['export'],
        ];

        // 2. Build Query with date constraints
        $query = $this->buildBaseQuery($filters, $validated['year'], $validated['months']);

        // 3. Get and group data
        $expenses = $this->applyPeriodGrouping($query, $filters['period'], $filters['type']);

        // 4. Generate Title
        $title = $this->buildExportTitle($filters, $validated['year'], $validated['months']);
        $fileName = 'expense_' . now()->format('Y-m-d_His');

        // 5. EXPORT (Let ExpenseExport handle transformation)
        if ($filters['export'] === 'pdf') {
            return Pdf::loadView('exports.expense', [
                'rows' => $expenses,
                'title' => $title,
                'tab' => $filters['tab'],
                'type' => $filters['type'],
                'source' => 'index',
            ])
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download($fileName . '.pdf');
        }

        // Excel/CSV
        return (new ExpenseExport(
            $expenses,
            $title,
            $filters['type'],
            'index'
        ))->download($fileName . '.' . ($filters['export'] === 'excel' ? 'xlsx' : 'csv'));
    }

    public function exportShow(Request $request)
    {
        $validated = $request->validate([
            'start'     => ['required', 'date'],
            'end'       => ['required', 'date'],
            'label'     => ['required', 'string'],
            'tab'       => ['required', 'string', 'exists:vehicle_types,name'],
            'type'      => ['required', 'string', Rule::in(['franchise', 'branch'])],
            'franchise' => ['nullable'],
            'branch'    => ['nullable'],
            'export'     => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
        ]);

        // 1. Determine which ID we are filtering for
        $id = $validated['type'] === 'franchise' ? $validated['franchise'] : $validated['branch'];
        
        // 2. Normalize filters for the buildBaseQuery
        $filters = [
            'tab'       => $validated['tab'] ?? 'taxi',
            'type'      => $validated['type'] ?? 'franchise',
            'franchise' => [$id] ?? [],
            'branch'    => [$id] ?? [],
            'export' => $validated['export'],
        ];

        // 3. Fetch specific Target Name for header
        $targetName = 'N/A';
        if ($validated['type'] === 'franchise' && $id) {
            $targetName = Franchise::find($id)?->name;
        } elseif ($validated['type'] === 'branch' && $id) {
            $targetName = Branch::find($id)?->name;
        }

        // 4. Build Query
        $query = $this->buildBaseQuery($filters);

        // Filter by the exact date range from the clicked row
        $query->whereBetween(DB::raw('DATE(payment_date)'), [
            $validated['start'], 
            $validated['end']
        ]);

        $details = $query->with([
                'maintenance.vehicle:id,plate_number', 
                'maintenance.inventory:id,name'])
            ->orderBy('payment_date', 'desc')
            ->get();

        // 4. Generate Title
        $title = $targetName . ' ' . ucfirst($validated['tab']) . ' ' . 'Maintenance Expenses for ' . $validated['label'];
        $fileName = 'expenses_' . $targetName . '_' . $validated['tab'] . '_' . now()->format('Y-m-d_His');

        // 5. EXPORT (Let ExpenseExport handle transformation)
        if ($filters['export'] === 'pdf') {
            return Pdf::loadView('exports.expense', [
                'rows' => $details,
                'title' => $title,
                'tab' => $filters['tab'],
                'type' => $filters['type'],
                'source' => 'show'
            ])
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download($fileName . '.pdf');
        }

        // Excel/CSV
        return (new ExpenseExport(
            $details,
            $title,
            $filters['type'],
            'show'
        ))->download($fileName . '.' . ($filters['export'] === 'excel' ? 'xlsx' : 'csv'));
    }

    /**
     * Helper to build a descriptive title for the export.
     */
    private function buildExportTitle(array $filters, int $year, array $months): string
    {
        $period = ucfirst($filters['period']);
        $typeName = $filters['type'] === 'franchise' ? 'Franchise' : 'Branch';
        $tabName = ucfirst($filters['tab'] ?? '');

        // Get specific name if filtered
        $targetName = "All {$typeName}s";
        if (!empty($filters['franchises'])) {
            $names = Franchise::whereIn('id', $filters['franchises'])
                ->pluck('name')
                ->join(', ');
            $targetName = $names ?: 'Franchise';
        } elseif (!empty($filters['branches'])) {
            $names = Branch::whereIn('id', $filters['branches'])
                ->pluck('name')
                ->join(', ');
            $targetName = $names ?: 'Branch';
        }

        // Format months
        $monthNames = collect($months)->map(fn ($m) => date('F', mktime(0, 0, 0, $m, 1)))->join(', ');

        return "{$period} {$tabName} Expense for {$targetName} - {$monthNames} {$year}";
    }
}
