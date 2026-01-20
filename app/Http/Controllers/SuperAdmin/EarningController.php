<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\EarningDatatableResource;
use App\Http\Resources\SuperAdmin\EarningShowResource;
use App\Models\Franchise;
use App\Models\Revenue;
use App\Models\PercentageType;
use App\Models\RevenueBreakdown;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use App\Models\UserDriver;
use Illuminate\Support\Str;
use App\Exports\EarningExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Inertia\Response;

class EarningController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'franchise' => ['sometimes', 'nullable', 'array'], 
            'driver' => ['sometimes', 'nullable', 'array'],
            'period' => ['sometimes', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
        ]);

        // 2. Set defaults
        $filters = [
            'franchise' => $validated['franchise'] ?? [],
            'driver' => $validated['driver'] ?? [],
            'period' => $validated['period'] ?? 'daily',
        ];
        
        // 3. Get Shared Data (Fee Types)
        $feeTypes = $this->getFeeTypes();

        // 4. Build Base Query
        $query = $this->buildBaseQuery($filters);

        // 5. Join the Breakdown Subquery (Modular Logic)
        $query = $this->joinBreakdownSubquery($query, $feeTypes);

        // 6. Apply Grouping & Calculations (Specific to Index)
        $earnings = $this->applyPeriodGrouping($query, $filters['period'], $feeTypes);
        $driversList = $this->getContextualDrivers($filters);

        // 7. Return all data to Inertia
        return Inertia::render('super-admin/finance/EarningIndex', [
            'earnings' => EarningDatatableResource::collection($earnings),
            'franchises' => fn () => Franchise::select('id', 'name')->get(),
            'drivers' => fn () => $driversList,
            'filters' => $filters,
            'feeTypes' => $feeTypes,
        ]);
    }

    public function show(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'driver' => ['required', 'string'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
            'label'   => ['required', 'string'],
        ]);

        $driverId = $validated['driver'];
        $dateLabel = $validated['label'];

        // 2. Get Shared Data
        $feeTypes = $this->getFeeTypes();

        // 3. Build Base Query (Reuse filters, but enforce specific driver)
        $filters = [
            'franchise' => $validated['franchise'] ?? [],
            'driver' => [$driverId], 
        ];

        // 4. Build Base Query
        $query = $this->buildBaseQuery($filters);

        // 5. Join the Breakdown Subquery (Modular Logic)
        $query = $this->joinBreakdownSubquery($query, $feeTypes);

        // 6. APPLY THE DATE FILTER 
        $query->whereRaw('DATE(revenues.payment_date) BETWEEN ? AND ?', [
            $validated['start'], 
            $validated['end']
        ]);

        // 7. Select Individual Transactions (No Group By)
        $selects = [
            'revenues.id',
            'revenues.invoice_no',
            'revenues.payment_date',
            'revenues.amount as total_amount',
            // Calculate Driver Earning per transaction
            DB::raw('(revenues.amount - COALESCE(breakdowns.total_deductions, 0)) as driver_earning')
        ];

        // Add dynamic fee columns
        foreach ($feeTypes as $type) {
            $selects[] = DB::raw("COALESCE(breakdowns.{$type['slug']}_amount, 0) as {$type['slug']}");
        }

        $transactions = $query->select($selects)
            ->orderBy('revenues.payment_date', 'desc')
            ->orderBy('revenues.id', 'desc')
            ->get();

        // 8. Get Driver Details for Header
        $driver = UserDriver::with('user')->find($driverId);

        return Inertia::render('super-admin/finance/EarningShow', [
            'details' => EarningShowResource::collection($transactions),
            'driver' => [
                'id' => $driver->id,
                'username' => $driver->user->username,
            ],
            'periodLabel' => $dateLabel,
            'feeTypes' => $feeTypes,
        ]);
    }

    /**
     * Returns a list of fee types.
     */
    private function getFeeTypes()
    {
        return PercentageType::all()->map(function ($type) {
            return [
                'id' => $type->id,
                'db_name' => $type->name,
                'display' => Str::of($type->name)->replace('_', ' ')->title()->toString(),
                'slug' => Str::snake($type->name),
            ];
        });
    }

    /**
     * The Core Calculation Logic: Pivots rows to columns. Index (aggregated) and Show (per transaction).
     */
    private function joinBreakdownSubquery(Builder $query, $feeTypes): Builder
    {
        $breakdownSelects = [
            'revenue_breakdowns.revenue_id',
            DB::raw('SUM(revenue_breakdowns.total_earning) as total_deductions')
        ];

        foreach ($feeTypes as $type) {
            $breakdownSelects[] = DB::raw("SUM(CASE WHEN percentage_types.name = '{$type['db_name']}' THEN total_earning ELSE 0 END) as {$type['slug']}_amount");
        }

        $breakdownSubquery = RevenueBreakdown::query()
            ->join('percentage_types', 'revenue_breakdowns.percentage_type_id', '=', 'percentage_types.id')
            ->select($breakdownSelects)
            ->groupBy('revenue_breakdowns.revenue_id');

        return $query->joinSub($breakdownSubquery, 'breakdowns', function ($join) {
            $join->on('revenues.id', '=', 'breakdowns.revenue_id');
        });
    }

    /**
     * Creates the base query with all "WHERE" conditions.
     */
    private function buildBaseQuery(array $filters, ?int $year = null, ?array $months = null): Builder
    {
        $query = Revenue::query()
            // Base filters: "paid" status and non-null payment_date
            ->whereHas('status', fn ($q) => $q->where('name', 'paid'))
            ->whereNotNull('payment_date')
            ->where('service_type', 'Trips');

        // --- Apply date constraints for export only ---
        if ($year) {
            $query->whereYear('payment_date', $year);
        }
        if (!empty($months)) {
            $query->whereIn(DB::raw('MONTH(payment_date)'), $months);
        }

        // Filter by specific driver if selected
        $query->when(!empty($filters['driver']), function ($q) use ($filters) {
            $q->whereIn('driver_id', $filters['driver']);
        });

        $query->whereNotNull('franchise_id')
            ->when(!empty($filters['franchise']), fn ($q) => $q->whereIn('franchise_id', $filters['franchise']));

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

    /**
     * Applies the SELECT and GROUP BY logic based on the period.
     */
    private function applyPeriodGrouping(Builder $query, string $period, $feeTypes)
    {
        $query->join('user_drivers', 'revenues.driver_id', '=', 'user_drivers.id')
              ->join('users', 'user_drivers.id', '=', 'users.id');

        // Dynamic Selects for the Main Query Aggregation
        $selects = [
            DB::raw('SUM(revenues.amount) as total_amount'),
            // Driver Earning = Total Revenue - Total Deductions (calculated in subquery)
            DB::raw('(SUM(revenues.amount) - COALESCE(SUM(breakdowns.total_deductions), 0)) as driver_earning'),
            'users.username as driver_username',
            'users.id as driver_id',
        ];

        // Add SUM aggregators for each dynamic fee type
        foreach ($feeTypes as $type) {
            // "{$type['slug']}_amount" must match the alias in joinBreakdownSubquery
            $selects[] = DB::raw("SUM(breakdowns.{$type['slug']}_amount) as total_{$type['slug']}");
        }

        // Add Franchise Name
        $query->join('franchises', 'revenues.franchise_id', '=', 'franchises.id')
            ->addSelect('franchises.name as franchise_name')
            ->groupBy('franchises.id', 'franchises.name');
        
        // Apply Selects
        $query->addSelect($selects)
              ->groupBy('users.id', 'users.username');

        // Time Period Grouping
        if ($period === 'daily') {
            return $query->addSelect(DB::raw('DATE(revenues.payment_date) as payment_date'))
                ->groupByRaw('DATE(revenues.payment_date)')
                ->orderByRaw('DATE(revenues.payment_date) DESC')
                ->get();
        }
        if ($period === 'weekly') {
            return $query->addSelect(DB::raw('MIN(revenues.payment_date) as week_start, MAX(revenues.payment_date) as week_end'))
                ->groupByRaw('YEAR(revenues.payment_date), WEEK(revenues.payment_date, 1)')
                ->orderBy('week_start', 'desc')
                ->get();
        }
        if ($period === 'monthly') {
            return $query->addSelect(DB::raw('DATE_FORMAT(revenues.payment_date, "%M %Y") as month_name, MIN(revenues.payment_date) as month_sort'))
                ->groupByRaw('DATE_FORMAT(revenues.payment_date, "%M %Y")')
                ->orderBy('month_sort', 'desc')
                ->get();
        }

        return $query->get();
    }

    /**
     * Handles the export index process.
     */
    public function exportIndex(Request $request)
    {
        // 1. Validate all inputs (page filters + modal filters)
        $validated = $request->validate([
            'franchise' => ['sometimes', 'nullable', 'array'], 
            'driver' => ['sometimes', 'nullable', 'array'],
            'period' => ['required', 'string',Rule::in(['daily', 'weekly', 'monthly'])],
            'export' => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'months' => ['required', 'array', 'min:1'],
            'months.*' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $filters = [
            'franchise' => $validated['franchise'] ?? [],
            'driver' => $validated['driver'] ?? [],
            'period' => $validated['period'] ?? 'daily',
            'export' => $validated['export'] ?? 'pdf',
        ];

        // 2. Get Fee Types
        $feeTypes = $this->getFeeTypes();

        // 3. Build Query (Filters + Date Year/Month constraints)
        $query = $this->buildBaseQuery($filters, $validated['year'], $validated['months']);

        // 4. Join Subquery & Group (Reusing your efficient logic)
        $query = $this->joinBreakdownSubquery($query, $feeTypes);
        
        // 5. Get Data
        $revenues = $this->applyPeriodGrouping($query, $filters['period'], $feeTypes);

        // 6. Generate Title
        $title = $this->buildExportTitle($filters, $validated['year'], $validated['months']);
        $fileName = 'earnings_' . now()->format('Y-m-d_His');

        // 7. EXPORT
        if ($filters['export'] === 'pdf') {
            // Prepare data for PDF View
            return Pdf::loadView('exports.earning', [
                'rows' => $revenues,
                'title' => $title,
                'tab' => 'franchise',
                'feeTypes' => $feeTypes
            ])->setPaper('a4', 'landscape')->download($fileName.'.pdf');
        }

        // Excel/CSV
        return (new EarningExport(
            $revenues, 
            $title, 
            'franchise',
            $feeTypes // Pass fee types so Export knows what columns to create
        ))->download($fileName . '.' . ($filters['export'] === 'excel' ? 'xlsx' : 'csv'));
    }

    /**
     * Handles the export show process.
     */
    public function exportShow(Request $request)
    {
        // 1. Validate (Same as show method)
        $validated = $request->validate([
            'driver'     => ['required', 'string'],
            'start' => ['required', 'date'],
            'end'   => ['required', 'date'],
            'label'      => ['required', 'string'],
            'export'     => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
        ]);

        $driverId = $validated['driver'];
        $feeTypes = $this->getFeeTypes();

        // 2. Build Query (Same logic as show)
        $filters = [
            'driver' => [$driverId],
            'tab' => null,
        ];

        $query = $this->buildBaseQuery($filters);
        $query = $this->joinBreakdownSubquery($query, $feeTypes);
        
        // Apply Date Range
        $query->whereBetween('revenues.payment_date', [
            $validated['start'], 
            $validated['end']
        ]);

        // 3. Select Columns (Transaction Level)
        $selects = [
            'revenues.invoice_no', // Unique to Show
            'revenues.payment_date',
            'revenues.amount as total_amount',
            DB::raw('(revenues.amount - COALESCE(breakdowns.total_deductions, 0)) as driver_earning')
        ];

        foreach ($feeTypes as $type) {
            $selects[] = DB::raw("COALESCE(breakdowns.{$type['slug']}_amount, 0) as total_{$type['slug']}");
        }

        $transactions = $query->select($selects)
            ->orderBy('revenues.payment_date', 'desc')
            ->orderBy('revenues.id', 'desc')
            ->get();

        // 4. Prepare Metadata
        $driver = UserDriver::with('user')->find($driverId);
        $driverName = $driver->user->name;
        $title = "Trip Earning Details for {$driverName} - " . $validated['label'];
        $fileName = 'details_' . Str::slug($driverName) . '_' . now()->format('Y-m-d');

        // 5. Handle PDF
        if ($validated['export'] === 'pdf') {
            return Pdf::loadView('exports.earning', [
                'rows' => $transactions,
                'title' => $title,
                'tab' => null, // Signal that this is the Detail view
                'feeTypes' => $feeTypes,
                'isDetailView' => true // New flag
            ])->setPaper('a4', 'landscape')->download($fileName.'.pdf');
        }

        // 6. Handle Excel/CSV
        return (new EarningExport(
            $transactions,
            $title,
            'show', // Pass 'show' as the tab name to signal logic switch
            $feeTypes
        ))->download($fileName . '.' . ($validated['export'] === 'excel' ? 'xlsx' : 'csv'));
    }

    /**
     * Helper to build a descriptive title for the export.
     */
    private function buildExportTitle(array $filters, int $year, array $months): string
    {
        $period = ucfirst($filters['period']);
        $tabName = 'Franchise';

        // Get specific name if filtered
        $targetName = "All {$tabName}s";
        if (!empty($filters['franchise'])) {
            $names = Franchise::whereIn('id', $filters['franchise'])
                ->pluck('name')
                ->join(', ');
            $targetName = $names ?: 'Franchise';
        }

        // Format months
        $monthNames = collect($months)->map(fn ($m) => date('F', mktime(0, 0, 0, $m, 1)))->join(', ');

        return "{$period} Total Trip Earnings for {$targetName} - {$monthNames} {$year}";
    }
}
