<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Resources\Manager\DriverPayrollDatatableResource;
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

class PayrollDriverController extends Controller
{
    /**
     * Gets the current user's associated Branch model.
     * CHANGED: Renamed from getFranchiseOrDefault to getBranchOrDefault
     */
    protected function getBranchOrDefault()
    {
        return auth()->user()->managerDetails?->branches()->first();
    }

    public function index(Request $request): Response
    {
        // CHANGED: Use Branch logic
        $branch = $this->getBranchOrDefault();
        $branchId = $branch?->id;

        // Ensure a branch is found before proceeding.
        if (!$branchId) {
             return Inertia::render('manager/payroll/Index', [
                'revenues' => DriverPayrollDatatableResource::collection(collect()),
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
            // CHANGED: Use branch_id instead of franchise_id
            'branch_id' => $branchId, // MANDATORY filter, passed internally
            'driver' => $validated['driver'] ?? null,
            'service' => $validated['service'] ?? 'Trips',
            'period' => $validated['period'] ?? 'daily',
            // CHANGED: Set implicit tab to 'branch'
            'tab' => 'branch',
        ];

        // 3. Build and execute query
        $query = $this->buildBaseQuery($filters);
        $revenues = $this->applyPeriodGrouping($query, $filters['period']);
        $driversList = $this->getContextualDrivers($filters);

        // 4. Return all data to Inertia
        return Inertia::render('manager/payroll/Index', [
            'revenues' => DriverPayrollDatatableResource::collection($revenues),
            'drivers' => fn () => $driversList,
            'filters' => $filters,
        ]);
    }

    /**
     * Retrieves drivers only linked to the logged-in manager's branch.
     * CHANGED: Updated to check 'branches' relationship instead of 'franchises'.
     */
    private function getContextualDrivers(array $filters)
    {
        // CHANGED: Use branch_id
        $branchId = $filters['branch_id'];

        $query = UserDriver::query()
            ->join('users', 'user_drivers.id', '=', 'users.id')
            ->select('user_drivers.id', 'users.username');

        // MANDATORY: Filter drivers by the manager's branch
        // CHANGED: Filter by branch relationship
        $query->whereHas('branches', function ($q) use ($branchId) {
            $q->where('branches.id', $branchId);
        });

        return $query->orderBy('users.username')->get();
    }

    /**
     * Creates the base query with all "WHERE" conditions, now limited by Branch ID.
     * CHANGED: Updated to filter by Branch ID.
     */
    private function buildBaseQuery(array $filters, ?int $year = null, ?array $months = null): Builder
    {
        // CHANGED: Use branch_id
        $branchId = $filters['branch_id'];

        $query = Revenue::query()
            ->whereHas('status', fn ($q) => $q->where('name', 'paid'))
            ->whereNotNull('payment_date')
            ->where('service_type', $filters['service']);

        // --- MANDATORY: Apply Branch Filter based on session ---
        // CHANGED: Filter on revenue's branch_id
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
     *
     * @param Builder $query
     * @param string $period
     * @return \Illuminate\Support\Collection
     */
    private function applyPeriodGrouping(
        Builder $query,
        string $period,
    ) {
        // Fetch all breakdown types to dynamically generate SUM(CASE...) expressions
        $breakdownTypes = DB::table('percentage_types')->pluck('name');

        // Dynamically create select statements for breakdown sums (Needed for export breakdown totals)
        $breakdownSelects = $breakdownTypes->map(function ($type) {
            return "SUM(CASE WHEN percentage_types.name = '{$type}' THEN revenue_breakdowns.total_earning ELSE 0 END) AS breakdown_{$type}";
        })->join(', ');

        // --- NEW: Select statement for the total deduction (sum of all breakdowns) ---
        $totalDeductionSelect = 'SUM(revenue_breakdowns.total_earning) AS total_deduction';
        $fullSelects = $breakdownSelects . ", " . $totalDeductionSelect;


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

        // 3. Add Tab-specific Joins and Selects (Updated to Branch logic)
        $groupingFields = ['users.id', 'users.username', 'revenues.service_type'];

        // CHANGED: Join to branches table
        $query->join('branches', 'revenues.branch_id', '=', 'branches.id')
            ->addSelect('branches.id as branch_id', 'branches.name as branch_name');
        $groupingFields[] = 'branches.id';
        $groupingFields[] = 'branches.name';

        // --- Handle Daily Period (GROUPED QUERY) ---
        if ($period === 'daily') {
            $query->selectRaw($groupingSelects . ", DATE(revenues.payment_date) as daily_date_sort, " . $fullSelects);

            $query->groupBy(array_merge($groupingFields, [DB::raw('DATE(revenues.payment_date)'), 'revenues.service_type']))
                ->orderBy('daily_date_sort', 'desc');

            return $query->get();
        }

        // --- Handle Weekly/Monthly (Grouped Query with JOINs) ---

        // Apply period-specific grouping
        if ($period === 'weekly') {
            $query->selectRaw($groupingSelects . ", MIN(revenues.payment_date) as week_start, MAX(revenues.payment_date) as week_end, " . $fullSelects);

            $query->groupBy(array_merge($groupingFields, [DB::raw('YEAR(revenues.payment_date)'), DB::raw('WEEK(revenues.payment_date, 1)'), 'revenues.service_type']))
                ->orderBy('week_start', 'desc');
        }

        if ($period === 'monthly') {
            $query->selectRaw($groupingSelects . ",
                DATE_FORMAT(revenues.payment_date, '%M %Y') as month_name,
                YEAR(revenues.payment_date) as year_sort,
                MONTH(revenues.payment_date) as month_sort,
                " . $fullSelects);

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
     * Handles the export process (MODIFIED for manager branch scope).
     */
    public function export(Request $request)
    {
        // CHANGED: Use Branch logic
        $branch = $this->getBranchOrDefault();
        $branchId = $branch?->id;

        if (!$branchId) {
            // CHANGED: Error message
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
            // CHANGED: Use branch_id
            'branch_id' => $branchId, // MANDATORY filter, passed internally
            'driver' => $validated['driver'] ?? null,
            'service' => $validated['service'] ?? 'Trips',
            'period' => $validated['period'] ?? 'daily',
            'export_type' => $validated['export_type'] ?? 'pdf',
            // CHANGED: Set implicit tab to 'branch'
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
            $filters['period']
        );

        // --- CALCULATE GRAND TOTALS AND GET TYPES FROM GROUPED DATA ---
        $breakdownTypes = DB::table('percentage_types')->pluck('name')->toArray();
        $breakdownKeys = array_map(fn ($type) => "breakdown_{$type}", $breakdownTypes);

        $grandTotalDeduction = $revenues->sum('total_deduction');


        // 4. Transform data for export
        $resourceCollection = DriverPayrollDatatableResource::collection($revenues);
        $arrayData = collect($resourceCollection->toArray(request()));

        $exportRows = $arrayData->map(function ($r) use ($filters) {
            // CHANGED: Use branch_name
            $tabNameValue = $r['branch_name'] ?? 'N/A'; // Start with branch name

            $row = [
                'driver_username' => $r['driver_username'] ?? 'N/A',
                'tab_name' => $tabNameValue, // Will be Branch Name
                'period' => $r['payment_date'] ?? '',
                'amount' => (float) ($r['amount'] ?? 0),
            ];

            // We use the computed total_deduction from the query
            $calculatedDeduction = (float)($r['total_deduction'] ?? 0);
            $row['total_deduction'] = $calculatedDeduction;

            $driverEarning = max(0, $row['amount'] - $calculatedDeduction);
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

        // Add Grand Total Deduction using the summed value
        $grandTotalRow['total_deduction'] = (float) $grandTotalDeduction;

        $grandTotalRow['driver_earning'] = (float) $grandTotalDriverEarning;
        $exportRows = $exportRows->push($grandTotalRow);

        // 6. Define Headings - CHANGED: 'Franchise' to 'Branch'
        $headings = [
            'Driver Username',
            'Branch', // CHANGED
            'Date',
            'Amount',
            'Deduction',
            'Driver Earning',
        ];

        // Define data keys for PDF/Excel mapping (Remains the same keys as the data structure is unchanged, only the content is branch-specific)
        $dataKeys = [
            'driver_username', 'tab_name', 'period', 'amount', 'total_deduction', 'driver_earning'
        ];

        // Build title - CHANGED: Pass branch name
        $title = $this->buildExportTitleSimplified($filters, $branch->name, $validated['year'], $validated['months']);
        $fileName = 'driver_payroll_report_'.date('Y-m-d'); // Adjusted file name

        // 7. Dispatch Download
        $export = new DriverExport($exportRows, $headings, $title, $dataKeys);
        $exportType = $filters['export_type'];

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
     * Helper to build a descriptive title for the export (MODIFIED for manager/branch).
     */
    private function buildExportTitleSimplified(array $filters, string $branchName, int $year, array $months): string
    {
        $period = ucfirst($filters['period']);
        $service = $filters['service'];

        // Format months
        $monthNames = collect($months)->map(fn ($m) => date('F', mktime(0, 0, 0, $m, 1)))->join(', ');

        // CHANGED: Reference branch name
        return "{$period} {$service} Revenue Summary for Driver Payroll - {$branchName} - {$monthNames} {$year}";
    }
}
