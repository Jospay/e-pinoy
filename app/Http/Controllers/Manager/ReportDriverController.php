<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\Manager\DriverReportDatatableResource;
use App\Models\Revenue;
use App\Models\UserDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\DriverExport;
use Maatwebsite\Excel\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Inertia\Response;

class ReportDriverController extends Controller
{
    /**
     * Gets the current user's associated Branch model.
     */
    protected function getBranchOrDefault()
    {
        // Assuming the Manager is always associated with a Branch
        return auth()->user()->managerDetails?->branches()->first();
    }

    public function index(Request $request): Response
    {
        $branch = $this->getBranchOrDefault();
        $branchId = $branch?->id;

        // Ensure a Branch is found before proceeding.
        if (!$branchId) {
             return Inertia::render('manager/driver-report/Index', [
                'revenues' => DriverReportDatatableResource::collection(collect()),
                'drivers' => [],
                'filters' => [
                    'driver' => null,
                    'service' => 'Trips',
                    'period' => 'daily',
                ],
            ]);
        }

        // 1. Validate only the remaining filters (driver, service, period)
        $validated = $request->validate([
            'driver' => ['sometimes', 'nullable', 'string'],
            'service' => ['sometimes', 'string', Rule::in(['Trips'])],
            'period' => ['sometimes', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
        ]);

        // 2. Set defaults
        $filters = [
            'branch_id' => $branchId, // MANDATORY filter, passed internally
            'driver' => $validated['driver'] ?? null,
            'service' => $validated['service'] ?? 'Trips',
            'period' => $validated['period'] ?? 'daily',
            // Set an implicit tab for export and grouping logic, even if the URL doesn't control it
            'tab' => 'branch',
        ];

        // 3. Build and execute query
        $query = $this->buildBaseQuery($filters);
        $revenues = $this->applyPeriodGrouping($query, $filters['period'], $filters['tab']);
        $driversList = $this->getContextualDrivers($filters);

        // 4. Return all data to Inertia
        return Inertia::render('manager/driver-report/Index', [
            'revenues' => DriverReportDatatableResource::collection($revenues),
            'drivers' => fn () => $driversList,
            'filters' => $filters,
        ]);
    }

    /**
     * Retrieves drivers only linked to the logged-in Manager's Branch.
     */
    private function getContextualDrivers(array $filters)
    {
        $branchId = $filters['branch_id'];

        $query = UserDriver::query()
            ->join('users', 'user_drivers.id', '=', 'users.id')
            ->select('user_drivers.id', 'users.username');

        // MANDATORY: Filter drivers by the Manager's Branch
        $query->whereHas('branches', function ($q) use ($branchId) {
            $q->where('branches.id', $branchId);
        });

        return $query->orderBy('users.username')->get();
    }

    /**
     * Creates the base query with all "WHERE" conditions, now limited by Branch ID.
     */
    private function buildBaseQuery(array $filters, ?int $year = null, ?array $months = null): Builder
    {
        $branchId = $filters['branch_id'];

        $query = Revenue::query()
            ->whereHas('status', fn ($q) => $q->where('name', 'paid'))
            ->whereNotNull('payment_date')
            ->where('service_type', $filters['service']);

        // --- MANDATORY: Apply Branch Filter based on session ---
        $query->where('branch_id', $branchId);

        // --- Apply date constraints for export only ---
        if ($year) {
            $query->whereYear('payment_date', $year);
        }
        if (! empty($months)) {
            $query->whereIn(DB::raw('MONTH(payment_date)'), $months);
        }

        // --- Apply Driver Filter ---
        if (!empty($filters['driver']) && $filters['driver'] !== 'all') {
            $query->where('driver_id', $filters['driver']);
        }

        return $query;
    }

    /**
     * Applies the SELECT and GROUP BY logic based on the period.
     */
    private function applyPeriodGrouping(
        Builder $query,
        string $period,
    ) {
        // Fetch all breakdown types to dynamically generate SUM(CASE...) expressions
        $breakdownTypes = DB::table('percentage_types')->pluck('name');

        // Dynamically create select statements for breakdown sums
        $breakdownSelects = $breakdownTypes->map(function ($type) {
            return "SUM(CASE WHEN percentage_types.name = '{$type}' THEN revenue_breakdowns.total_earning ELSE 0 END) AS breakdown_{$type}";
        })->join(', ');

        // 1. Core Joins (needed for all grouped reports)
        $query->join('users', 'revenues.driver_id', '=', 'users.id')
              ->leftJoin('revenue_breakdowns', 'revenues.id', '=', 'revenue_breakdowns.revenue_id')
              ->leftJoin('percentage_types', 'revenue_breakdowns.percentage_type_id', '=', 'percentage_types.id');

        // 2. Base Grouping fields for all periods
        $groupingSelects = "
            SUM(DISTINCT revenues.amount) as total_amount,
            revenues.service_type,
            users.id as driver_id,
            users.username as driver_username
        ";

        // 3. Add Tab-specific Joins and Selects (Kept logic but uses implicit 'Branch' tab)
        $groupingFields = ['users.id', 'users.username', 'revenues.service_type'];

        // Since the report is scoped to ONE Branch, we can enforce the join to get names
        // If revenue has a branch_id, join the name
        $query->join('branches', 'revenues.branch_id', '=', 'branches.id')
            ->addSelect('branches.id as branch_id', 'branches.name as branch_name');
        $groupingFields[] = 'branches.id';
        $groupingFields[] = 'branches.name';

        // --- Handle Daily Period (GROUPED QUERY) ---
        if ($period === 'daily') {
            $query->selectRaw($groupingSelects . ", DATE(revenues.payment_date) as daily_date_sort, " . $breakdownSelects);

            $query->groupBy(array_merge($groupingFields, [DB::raw('DATE(revenues.payment_date)'), 'revenues.service_type']))
                ->orderBy('daily_date_sort', 'desc');

            return $query->get();
        }

        // --- Handle Weekly/Monthly (Grouped Query with JOINs) ---

        // Apply period-specific grouping
        if ($period === 'weekly') {
            $query->selectRaw($groupingSelects . ", MIN(revenues.payment_date) as week_start, MAX(revenues.payment_date) as week_end, " . $breakdownSelects);

            $query->groupBy(array_merge($groupingFields, [DB::raw('YEAR(revenues.payment_date)'), DB::raw('WEEK(revenues.payment_date, 1)'), 'revenues.service_type']))
                ->orderBy('week_start', 'desc');
        }

        if ($period === 'monthly') {
            $query->selectRaw($groupingSelects . ",
                DATE_FORMAT(revenues.payment_date, '%M %Y') as month_name,
                YEAR(revenues.payment_date) as year_sort,
                MONTH(revenues.payment_date) as month_sort,
                " . $breakdownSelects);

            $query->groupBy(array_merge($groupingFields, [
                DB::raw('YEAR(revenues.payment_date)'),
                DB::raw('MONTH(revenues.payment_date)'),
                DB::raw("DATE_FORMAT(revenues.payment_date, '%M %Y')"),
                'revenues.service_type'
            ]))
                ->orderBy('year_sort', 'desc')
                ->orderBy('month_sort', 'desc');
        }

        return $query->get();
    }

    /**
     * Handles the export process (RESTORED and MODIFIED for Manager scope).
     */
    public function export(Request $request)
    {
        $branch = $this->getBranchOrDefault();
        $branchId = $branch?->id;

        if (!$branchId) {
            return response()->json(['error' => 'Branch not found for the current user.'], 403);
        }

        // 1. Validate all inputs (page filters + modal filters)
        $validated = $request->validate([
            'driver' => ['nullable', 'string'],
            'service' => ['required', 'string', Rule::in(['Trips'])],
            'period' => ['required', 'string',Rule::in(['daily', 'weekly', 'monthly'])],
            'export_type' => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'months' => ['required', 'array', 'min:1'],
            'months.*' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $filters = [
            'branch_id' => $branchId, // MANDATORY filter, passed internally
            'driver' => $validated['driver'] ?? null,
            'service' => $validated['service'] ?? 'Trips',
            'period' => $validated['period'] ?? 'daily',
            'export_type' => $validated['export_type'] ?? 'pdf',
            // Set an implicit tab to 'Branch' for the grouping and title logic
            'tab' => 'branch',
        ];

        // 2. Build Query with date constraints
        $query = $this->buildBaseQuery(
            $filters,
            $validated['year'],
            $validated['months']
        );

        // 3. Get and group data
        $revenues = $this->applyPeriodGrouping(
            $query,
            $filters['period'],
            $filters['tab']
        );

        // --- CALCULATE GRAND TOTALS AND GET TYPES FROM GROUPED DATA ---
        $breakdownTypes = DB::table('percentage_types')->pluck('name')->toArray();
        $breakdownKeys = array_map(fn ($type) => "breakdown_{$type}", $breakdownTypes);

        $breakdownTotals = collect($breakdownKeys)->mapWithKeys(function ($key) use ($revenues) {
            $originalName = str_replace('breakdown_', '', $key);
            $total = $revenues->sum($key);
            return [$originalName => (float) $total];
        });
        $breakdownTypes = $breakdownTotals->keys()->toArray();

        // 4. Transform data for export
        $resourceCollection = DriverReportDatatableResource::collection($revenues);
        $arrayData = collect($resourceCollection->toArray(request()));

        $exportRows = $arrayData->map(function ($r) use ($filters, $breakdownTypes) {
            $tabNameValue = $r['branch_name'] ?? 'N/A'; // Start with Branch name


            $totalBreakdowns = 0;
            $row = [
                'driver_username' => $r['driver_username'] ?? 'N/A',
                'tab_name' => $tabNameValue,
                'period' => $r['payment_date'] ?? '',
                'amount' => (float) ($r['amount'] ?? 0),
            ];

            foreach ($breakdownTypes as $type) {
                $key = strtolower($type);
                $breakdownValue = (float) ($r[$key] ?? 0);
                $row[$key] = $breakdownValue;
                $totalBreakdowns += $breakdownValue;
            }

            $driverEarning = max(0, $row['amount'] - $totalBreakdowns);
            $row['driver_earning'] = (float) $driverEarning;

            return $row;
        })->values();

        // 5. Add Grand Total Row
        $grandTotal = $exportRows->sum('amount');
        $grandTotalDriverEarning = $exportRows->sum('driver_earning');

        $grandTotalRow = [
            'driver_username' => 'GRAND TOTAL',
            'tab_name' => '', // Empty for grand total row
            'period' => '',
            'amount' => (float) $grandTotal,
        ];

        foreach ($breakdownTypes as $type) {
            $key = strtolower($type);
            $grandTotalRow[$key] = (float) ($breakdownTotals[$type] ?? 0);
        }

        $grandTotalRow['driver_earning'] = (float) $grandTotalDriverEarning;
        $exportRows = $exportRows->push($grandTotalRow);

        // 6. Define Headings
        $headings = [
            'Driver Name',
            'Branch',
            'Date',
            'Amount',
        ];

        // Append breakdown headings
        $breakdownHeadings = array_map(fn ($type) => ucwords(str_replace('_', ' ', $type)), $breakdownTypes);
        $headings = array_merge($headings, $breakdownHeadings);
        $headings[] = 'Driver Earning';

        // Build title
        $title = $this->buildExportTitleSimplified($filters, $branch->name, $validated['year'], $validated['months']);
        $fileName = 'driver_report_'.date('Y-m-d');

        // 7. Dispatch Download
        $export = new DriverExport($exportRows, $headings, $title);
        $exportType = $filters['export_type'];

        $dataKeys = [
            'driver_username', 'tab_name', 'period', 'amount'
        ];
        $dataKeys = array_merge($dataKeys, array_keys($breakdownTotals->toArray()));
        $dataKeys[] = 'driver_earning';

        if ($exportType === 'pdf') {
            return Pdf::loadView('exports.driver', [
                'rows' => $exportRows,
                'title' => $title,
                'headings' => $headings,
                'dataKeys' => $dataKeys
            ])
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download($fileName.'.pdf');
        }
        if ($exportType === 'excel') {
            return $export->download($fileName.'.xlsx', Excel::XLSX);
        }
        if ($exportType === 'csv') {
            return $export->download($fileName.'.csv', Excel::CSV);
        }
    }

    /**
     * Helper to build a descriptive title for the export (SIMPLIFIED for Manager).
     */
    private function buildExportTitleSimplified(array $filters, string $branchName, int $year, array $months): string
    {
        $period = ucfirst($filters['period']);
        $service = $filters['service'];

        // Format months
        $monthNames = collect($months)->map(fn ($m) => date('F', mktime(0, 0, 0, $m, 1)))->join(', ');

        return "{$period} {$service} Revenue for Driver Report - {$branchName} - {$monthNames} {$year}";
    }
}
