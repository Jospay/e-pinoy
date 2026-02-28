<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Owner\DriverReportDatatableResource;
use App\Models\PercentageType;
use App\Models\Revenue;
use App\Models\User;
use App\Models\UserDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
     * Gets the current user's associated Franchise model.
     */
    protected function getFranchiseOrDefault()
    {
        return auth()->user()->ownerDetails?->franchises()->first();
    }

    public function index(Request $request): Response
    {
        $franchise = $this->getFranchiseOrDefault();
        $franchiseId = $franchise?->id;

        if (!$franchiseId) {
            return Inertia::render('owner/driver-report/Index', [
                'revenues' => DriverReportDatatableResource::collection(collect()),
                'drivers' => [],
                'branches' => [],
                'franchiseVehicleTypes' => [],
                'filters' => [
                    'driver' => 'all',
                    'service' => 'Trips',
                    'period' => 'daily',
                    'branch_id' => 'all',
                    'vehicle_type' => null,
                ],
            ]);
        }

        $franchiseVehicleTypes = \App\Models\VehicleType::whereHas('franchises', function($q) use ($franchiseId) {
            $q->where('franchise_id', $franchiseId);
        })->get();

        $branches = $franchise->branches;

        $validated = $request->validate([
            'driver'       => ['sometimes', 'nullable', 'string'],
            'service'      => ['sometimes', 'string', Rule::in(['Trips', 'Boundary'])],
            'period'       => ['sometimes', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'branch_id'    => ['sometimes', 'nullable', 'string'],
            'vehicle_type' => ['sometimes', 'nullable', 'string'],
        ]);

        $selectedType = $request->vehicle_type ?: $franchiseVehicleTypes->first()?->name;

        $filters = [
            'franchise_id' => $franchiseId,
            'driver'       => $validated['driver'] ?? 'all',
            'service'      => $validated['service'] ?? 'Trips',
            'period'       => $validated['period'] ?? 'daily',
            'branch_id'    => $validated['branch_id'] ?? 'all',
            'vehicle_type' => $selectedType,
            'tab'          => 'franchise',
        ];

        $query = $this->buildBaseQuery($filters);
        $revenues = $this->applyPeriodGrouping($query, $filters['period'], $filters['tab']);
        $driversList = $this->getContextualDrivers($filters);

        return Inertia::render('owner/driver-report/Index', [
            'revenues' => DriverReportDatatableResource::collection($revenues),
            'drivers'  => fn () => $driversList,
            'branches' => $branches,
            'franchiseVehicleTypes' => $franchiseVehicleTypes,
            'filters'  => $filters,
        ]);
    }

    private function getContextualDrivers(array $filters)
    {
        $franchiseId = $filters['franchise_id'];
        $query = UserDriver::query()
            ->join('users', 'user_drivers.id', '=', 'users.id')
            ->select('user_drivers.id', 'users.username');

        $query->whereHas('franchises', function ($q) use ($franchiseId) {
            $q->where('franchises.id', $franchiseId);
        });

        return $query->orderBy('users.username')->get();
    }

    private function buildBaseQuery(array $filters, ?int $year = null, ?array $months = null): Builder
    {
        $query = Revenue::query()
            ->whereHas('status', fn ($q) => $q->where('name', 'paid'))
            ->whereNotNull('payment_date')
            ->where('service_type', $filters['service'])
            ->where('revenues.franchise_id', $filters['franchise_id']);

        if (!empty($filters['vehicle_type'])) {
            $query->whereHas('vehicleType', function($q) use ($filters) {
                $q->where('name', $filters['vehicle_type']);
            });
        }

        if (!empty($filters['branch_id']) && $filters['branch_id'] !== 'all') {
            if ($filters['branch_id'] === 'franchise') {
                $query->whereNull('branch_id');
            } elseif ($filters['branch_id'] === 'only_branches') {
                $query->whereNotNull('branch_id');
            } else {
                $query->where('branch_id', $filters['branch_id']);
            }
        }

        if ($year) { $query->whereYear('payment_date', $year); }
        if (!empty($months)) { $query->whereIn(DB::raw('MONTH(payment_date)'), $months); }

        if (!empty($filters['driver']) && $filters['driver'] !== 'all') {
            $query->where('driver_id', $filters['driver']);
        }

        return $query;
    }

    private function applyPeriodGrouping(Builder $query, string $period)
    {
        $breakdownTypes = DB::table('percentage_types')->pluck('name');
        $breakdownSelects = $breakdownTypes->map(function ($type) {
            return "SUM(CASE WHEN percentage_types.name = '{$type}' THEN revenue_breakdowns.total_earning ELSE 0 END) AS breakdown_{$type}";
        })->join(', ');

        $query->join('users', 'revenues.driver_id', '=', 'users.id')
              ->leftJoin('revenue_breakdowns', 'revenues.id', '=', 'revenue_breakdowns.revenue_id')
              ->leftJoin('percentage_types', 'revenue_breakdowns.percentage_type_id', '=', 'percentage_types.id')
              ->leftJoin('branches', 'revenues.branch_id', '=', 'branches.id')
              ->join('franchises', 'revenues.franchise_id', '=', 'franchises.id');

        $groupingSelects = "SUM(DISTINCT revenues.amount) as total_amount, revenues.service_type, users.id as driver_id, users.username as driver_username, franchises.id as franchise_id, franchises.name as franchise_name, branches.id as branch_id, branches.name as branch_name";
        $groupingFields = ['users.id', 'users.username', 'revenues.service_type', 'franchises.id', 'franchises.name', 'branches.id', 'branches.name'];

        if ($period === 'daily') {
            $query->selectRaw($groupingSelects . ", DATE(revenues.payment_date) as daily_date_sort, " . $breakdownSelects)
                  ->groupBy(array_merge($groupingFields, [DB::raw('DATE(revenues.payment_date)')]))
                  ->orderBy('daily_date_sort', 'desc');
            return $query->get();
        }

        if ($period === 'weekly') {
            $query->selectRaw($groupingSelects . ", MIN(revenues.payment_date) as week_start, MAX(revenues.payment_date) as week_end, " . $breakdownSelects)
                  ->groupBy(array_merge($groupingFields, [DB::raw('YEAR(revenues.payment_date)'), DB::raw('WEEK(revenues.payment_date, 1)')]))
                  ->orderBy('week_start', 'desc');
        }

        if ($period === 'monthly') {
            $query->selectRaw($groupingSelects . ", DATE_FORMAT(revenues.payment_date, '%M %Y') as month_name, YEAR(revenues.payment_date) as year_sort, MONTH(revenues.payment_date) as month_sort, " . $breakdownSelects)
                  ->groupBy(array_merge($groupingFields, [DB::raw('YEAR(revenues.payment_date)'), DB::raw('MONTH(revenues.payment_date)'), DB::raw("DATE_FORMAT(revenues.payment_date, '%M %Y')")]))
                  ->orderBy('year_sort', 'desc')->orderBy('month_sort', 'desc');
        }

        return $query->get();
    }

    public function export(Request $request)
    {
        $franchise = $this->getFranchiseOrDefault();
        $franchiseId = $franchise?->id;
        if (!$franchiseId) return response()->json(['error' => 'Franchise not found.'], 403);

        $validated = $request->validate([
            'driver' => ['nullable', 'string'],
            'service' => ['required', 'string', Rule::in(['Trips', 'Boundary'])],
            'period' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'export_type' => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'months' => ['required', 'array', 'min:1'],
            'months.*' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $filters = ['franchise_id' => $franchiseId, 'driver' => $validated['driver'] ?? null, 'service' => $validated['service'] ?? 'Trips', 'period' => $validated['period'] ?? 'daily', 'export_type' => $validated['export_type'] ?? 'pdf', 'vehicle_type' => $request->vehicle_type, 'tab' => 'franchise'];

        $query = $this->buildBaseQuery($filters, $validated['year'], $validated['months']);
        $revenues = $this->applyPeriodGrouping($query, $filters['period']);

        $breakdownTypes = DB::table('percentage_types')->pluck('name')->toArray();
        $breakdownKeys = array_map(fn ($type) => "breakdown_{$type}", $breakdownTypes);

        $breakdownTotals = collect($breakdownKeys)->mapWithKeys(function ($key) use ($revenues) {
            $originalName = str_replace('breakdown_', '', $key);
            return [$originalName => (float) $revenues->sum($key)];
        });
        $breakdownTypes = $breakdownTotals->keys()->toArray();

        $resourceCollection = DriverReportDatatableResource::collection($revenues);
        $arrayData = collect($resourceCollection->toArray(request()));

        $exportRows = $arrayData->map(function ($r) use ($breakdownTypes) {
            $totalBreakdowns = 0;
            $row = ['driver_username' => $r['driver_username'] ?? 'N/A', 'tab_name' => $r['branch_name'] ?: 'Main Franchise', 'period' => $r['payment_date'] ?? '', 'amount' => (float) ($r['amount'] ?? 0)];
            foreach ($breakdownTypes as $type) {
                $key = strtolower($type);
                $val = (float) ($r[$key] ?? 0);
                $row[$key] = $val; $totalBreakdowns += $val;
            }
            $row['driver_earning'] = (float) max(0, $row['amount'] - $totalBreakdowns);
            return $row;
        })->values();

        $grandTotalRow = ['driver_username' => 'GRAND TOTAL', 'tab_name' => '', 'period' => '', 'amount' => (float) $exportRows->sum('amount')];
        foreach ($breakdownTypes as $type) { $grandTotalRow[strtolower($type)] = (float) $breakdownTotals[$type]; }
        $grandTotalRow['driver_earning'] = (float) $exportRows->sum('driver_earning');
        $exportRows->push($grandTotalRow);

        $headings = array_merge(['Driver Name', 'Franchise / Branch', 'Date', 'Amount'], array_map(fn ($type) => ucwords(str_replace('_', ' ', $type)), $breakdownTypes), ['Driver Earning']);
        $title = $this->buildExportTitleSimplified($filters, $franchise->name, $validated['year'], $validated['months']);
        $fileName = 'driver_report_'.date('Y-m-d');

        $export = new DriverExport($exportRows, $headings, $title);
        if ($filters['export_type'] === 'pdf') {
            return Pdf::loadView('exports.driver', ['rows' => $exportRows, 'title' => $title, 'headings' => $headings, 'dataKeys' => array_merge(['driver_username', 'tab_name', 'period', 'amount'], array_map('strtolower', $breakdownTypes), ['driver_earning'])])
                ->setPaper('a4', 'landscape')->download($fileName.'.pdf');
        }
        return $export->download($fileName.($filters['export_type'] === 'excel' ? '.xlsx' : '.csv'), $filters['export_type'] === 'excel' ? Excel::XLSX : Excel::CSV);
    }

    private function buildExportTitleSimplified(array $filters, string $franchiseName, int $year, array $months): string
    {
        $monthNames = collect($months)->map(fn ($m) => date('F', mktime(0, 0, 0, $m, 1)))->join(', ');
        return ucfirst($filters['period']) . " {$filters['vehicle_type']} Total Trip Earnings for {$franchiseName} - {$monthNames} {$year}";
    }

    // --- UPDATED SHOW METHOD ---
    public function show(Request $request)
    {
        $franchise = $this->getFranchiseOrDefault();
        if (!$franchise) abort(403, 'Franchise not found.');

        $driverId = $request->driver_id;
        $paymentDate = $request->payment_date;
        $period = $request->period ?? 'daily';

        $query = Revenue::with(['driver', 'revenueBreakdowns.percentageType', 'branch'])
            ->where('revenues.franchise_id', $franchise->id)
            ->where('driver_id', $driverId)
            ->where('service_type', 'Trips')
            ->whereHas('status', fn($q) => $q->where('name', 'paid'));

        if ($request->vehicle_type) {
            $query->whereHas('vehicleType', fn($q) => $q->where('name', $request->vehicle_type));
        }

        $this->applyDateFilters($query, $period, $paymentDate);
        $details = $query->get();
        $driver = User::findOrFail($driverId);

        $breakdownTypes = PercentageType::pluck('name')->map(fn($n) => ucwords(str_replace('_', ' ', $n)))->toArray();

        return inertia('owner/driver-report/Details', [
            'driver' => ['id' => $driver->id, 'username' => $driver->username],
            'periodLabel' => $paymentDate,
            'breakdownTypes' => $breakdownTypes,
            'details' => $details,
            'filters' => ['driver_id' => $driverId, 'period' => $period, 'vehicle_type' => $request->vehicle_type ?: 'Vehicle']
        ]);
    }

    // --- UPDATED EXPORT DETAILS METHOD ---
    public function exportDetails(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required', 'payment_date' => 'required', 'export_type' => 'required|in:pdf,excel,csv', 'period' => 'required'
        ]);

        $franchise = $this->getFranchiseOrDefault();
        $driver = User::findOrFail($validated['driver_id']);
        $query = Revenue::with(['revenueBreakdowns.percentageType', 'branch'])->where('franchise_id', $franchise->id)->where('driver_id', $validated['driver_id'])->where('service_type', 'Trips')->whereHas('status', fn($q) => $q->where('name', 'paid'));

        if ($request->vehicle_type) $query->whereHas('vehicleType', fn($q) => $q->where('name', $request->vehicle_type));
        $this->applyDateFilters($query, $validated['period'], $validated['payment_date']);

        $details = $query->get();
        $breakdownTypes = PercentageType::all();

        $exportRows = $details->map(function ($r) use ($breakdownTypes) {
            $total = (float)$r->amount; $breakSum = 0;
            $row = ['invoice_no' => $r->invoice_no, 'payment_date' => $r->payment_date, 'amount' => $total];
            foreach ($breakdownTypes as $t) {
                $val = (float)($r->revenueBreakdowns->firstWhere('percentage_type_id', $t->id)->total_earning ?? 0);
                $row[strtolower($t->name)] = $val; $breakSum += $val;
            }
            $row['driver_earning'] = max(0, $total - $breakSum);
            return $row;
        });

        $grandTotalRow = ['invoice_no' => 'GRAND TOTAL', 'payment_date' => '', 'amount' => (float)$exportRows->sum('amount')];
        foreach ($breakdownTypes as $t) { $grandTotalRow[strtolower($t->name)] = (float)$exportRows->sum(strtolower($t->name)); }
        $grandTotalRow['driver_earning'] = (float)$exportRows->sum('driver_earning');
        $exportRows->push($grandTotalRow);

        $headings = array_merge(['Invoice No.', 'Date/Time', 'Trip Amount'], $breakdownTypes->map(fn($t) => ucwords(str_replace('_', ' ', $t->name)))->toArray(), ['Driver Net']);
        $dataKeys = array_merge(['invoice_no', 'payment_date', 'amount'], $breakdownTypes->map(fn($t) => strtolower($t->name))->toArray(), ['driver_earning']);

        $vehicleLabel = ucfirst($request->vehicle_type ?? 'Vehicle');
        $title = $vehicleLabel . " Trip Earning Details for " . $driver->username . " - " . $validated['payment_date'];
        $fileName = 'Trip_Details_' . $driver->username . '_' . date('Y-m-d');

        if ($validated['export_type'] === 'pdf') {
            return Pdf::loadView('exports.driver_details', ['rows' => $exportRows, 'title' => $title, 'headings' => $headings, 'dataKeys' => $dataKeys])
                ->setPaper('a4', 'landscape')->download($fileName . '.pdf');
        }

        $export = new DriverExport($exportRows, $headings, $title);
        return $export->download($fileName . ($validated['export_type'] === 'excel' ? '.xlsx' : '.csv'), $validated['export_type'] === 'excel' ? Excel::XLSX : Excel::CSV);
    }

    private function applyDateFilters($query, $period, $paymentDate) {
        try {
            if ($period === 'daily') $query->whereDate('payment_date', Carbon::parse($paymentDate)->format('Y-m-d'));
            elseif ($period === 'monthly') $query->whereMonth('payment_date', Carbon::parse($paymentDate)->month)->whereYear('payment_date', Carbon::parse($paymentDate)->year);
            elseif ($period === 'weekly') {
                $start = explode(' - ', $paymentDate)[0];
                $date = Carbon::parse($start);
                $query->whereBetween('payment_date', [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()]);
            }
        } catch (\Exception $e) {}
    }
}
