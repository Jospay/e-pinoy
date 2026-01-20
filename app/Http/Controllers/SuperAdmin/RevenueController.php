<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\RevenueDatatableResource;
use App\Http\Resources\SuperAdmin\RevenueShowResource;
use App\Models\Franchise;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\RevenueExport;
use Maatwebsite\Excel\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Inertia\Response;

class RevenueController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'franchise' => ['sometimes', 'nullable', 'array'], 
            'service' => ['sometimes', 'string', Rule::in(['Trips', 'Boundary'])],
            'period' => ['sometimes', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
        ]);

        // 2. Set defaults
        $filters = [
            'franchise' => $validated['franchise'] ?? [],
            'service' => $validated['service'] ?? 'Trips',
            'period' => $validated['period'] ?? 'daily',
        ];

        // 3. Build and execute query
        $query = $this->buildBaseQuery($filters);
        $revenues = $this->applyPeriodGrouping($query, $filters['period']);

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/finance/RevenueIndex', [
            'revenues' => RevenueDatatableResource::collection($revenues),
            'franchises' => fn () => Franchise::select('id', 'name')->get(),
            'filters' => $filters,
        ]);
        
    }

    public function show(Request $request): Response
    {
        $validated = $request->validate([
            'start'     => ['required', 'date'],
            'end'       => ['required', 'date'],
            'label'     => ['required', 'string'],
            'service'   => ['required', 'string'],
            'franchise' => ['nullable'],
        ]);

        // 1. Determine which ID we are filtering for
        $id = $validated['franchise'];
        
        // 2. Normalize filters for the buildBaseQuery
        $filters = [
            'service'   => $validated['service'],
            'franchise' => [$id] ?? [],
        ];

        // 3. Fetch specific Target Name for header
        $targetName = Franchise::find($id)?->name ?: 'N/A';

        // 4. Build Query
        $query = $this->buildBaseQuery($filters);

        // Filter by the exact date range from the clicked row
        $query->whereBetween(DB::raw('DATE(payment_date)'), [
            $validated['start'], 
            $validated['end']
        ]);

        $details = $query->with('driver.user:id,username')
            ->orderBy('payment_date', 'desc')
            ->get();

        return Inertia::render('super-admin/finance/RevenueShow', [
            'details'     => RevenueShowResource::collection($details),
            'periodLabel' => $validated['label'],
            'targetName'  => $targetName,
            'totalSum'    => $details->sum('amount'),
            'filters'     => $filters,
        ]);
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
            ->where('service_type', $filters['service']);

            // --- Apply date constraints for export only ---
            if ($year) {
                $query->whereYear('payment_date', $year);
            }
            if (! empty($months)) {
                $query->whereIn(DB::raw('MONTH(payment_date)'), $months);
            }

        // Filter by specific franchise if selected
        $query->whereNotNull('franchise_id')
            ->when(!empty($filters['franchise']), fn ($q) => $q->whereIn('franchise_id', $filters['franchise']));

        return $query;
    }

    /**
     * Applies the SELECT and GROUP BY logic based on the period.
     */
    private function applyPeriodGrouping(Builder $query, string $period)
    {
        // Base selections for ALL periods (now including daily)
        $query->selectRaw('
            SUM(revenues.amount) as total_amount,
            revenues.service_type
        ');

        // Add JOINs and group by franchise
        $query->join('franchises', 'revenues.franchise_id', '=', 'franchises.id')
            ->addSelect('franchises.id as franchise_id', 'franchises.name as franchise_name')
            ->groupBy('franchises.id', 'franchises.name');
        

        // Apply period-specific grouping
        if ($period === 'daily') {
            $query->addSelect(DB::raw('DATE(revenues.payment_date) as payment_date'))
                ->groupBy(DB::raw('DATE(revenues.payment_date)'), 'revenues.service_type')
                ->orderBy('payment_date', 'desc');
        } elseif ($period === 'weekly') {
            $query->addSelect(DB::raw('MIN(revenues.payment_date) as week_start, MAX(revenues.payment_date) as week_end'))
                ->groupByRaw('YEAR(revenues.payment_date), WEEK(revenues.payment_date, 1), revenues.service_type')
                ->orderBy('week_start', 'desc');
        } elseif ($period === 'monthly') {
            $query->addSelect(DB::raw('DATE_FORMAT(revenues.payment_date, "%M %Y") as month_name, MIN(revenues.payment_date) as month_sort'))
                ->groupBy('month_name', 'revenues.service_type')
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
            'franchise' => ['sometimes', 'nullable', 'array'], 
            'service' => ['required', 'string', Rule::in(['Trips', 'Boundary'])],
            'period' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'export' => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'months' => ['required', 'array', 'min:1'],
            'months.*' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $filters = [
            'franchise' => $validated['franchise'] ?? [],
            'service' => $validated['service'],
            'period' => $validated['period'],
            'export' => $validated['export'],
        ];

        // 2. Build Query with date constraints
        $query = $this->buildBaseQuery($filters, $validated['year'], $validated['months']);

        // 3. Get and group data
        $revenues = $this->applyPeriodGrouping($query, $filters['period']);

        // 4. Generate Title
        $title = $this->buildExportTitle($filters, $validated['year'], $validated['months']);
        $fileName = 'revenues_' . now()->format('Y-m-d_His');

        // 5. EXPORT (Let RevenueExport handle transformation)
        if ($filters['export'] === 'pdf') {
            return Pdf::loadView('exports.revenue', [
                'rows' => $revenues,
                'title' => $title,
                'tab' => 'franchise',
                'source' => 'index',
            ])
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download($fileName . '.pdf');
        }

        // Excel/CSV
        return (new RevenueExport(
            $revenues,
            $title,
            'franchise',
            'index'
        ))->download($fileName . '.' . ($filters['export'] === 'excel' ? 'xlsx' : 'csv'));
    }

    public function exportShow(Request $request)
    {
        $validated = $request->validate([
            'start'     => ['required', 'date'],
            'end'       => ['required', 'date'],
            'label'     => ['required', 'string'],
            'service'   => ['required', 'string'],
            'franchise' => ['nullable'],
            'export'     => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
        ]);

        // 1. Determine which ID we are filtering for
        $id = $validated['franchise'];
        
        // 2. Normalize filters for the buildBaseQuery
        $filters = [
            'service'   => $validated['service'],
            'franchise' => [$id] ?? [],
            'export' => $validated['export'],
        ];

        // 3. Fetch specific Target Name for header
        $targetName = Franchise::find($id)?->name ?: 'N/A';

        // 4. Build Query
        $query = $this->buildBaseQuery($filters);

        // Filter by the exact date range from the clicked row
        $query->whereBetween(DB::raw('DATE(payment_date)'), [
            $validated['start'], 
            $validated['end']
        ]);

        $details = $query->with('driver.user:id,username')
            ->orderBy('payment_date', 'desc')
            ->get();

        // 4. Generate Title
        $title = $targetName . ' ' . $validated['service'] . ' Revenue for ' . $validated['label'];
        $fileName = 'revenues_' . $targetName . '_' . $validated['service'] . '_' . now()->format('Y-m-d_His');

        // 5. EXPORT (Let RevenueExport handle transformation)
        if ($filters['export'] === 'pdf') {
            return Pdf::loadView('exports.revenue', [
                'rows' => $details,
                'title' => $title,
                'tab' => 'franchise',
                'source' => 'show'
            ])
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download($fileName . '.pdf');
        }

        // Excel/CSV
        return (new RevenueExport(
            $details,
            $title,
            'franchise',
            'show'
        ))->download($fileName . '.' . ($filters['export'] === 'excel' ? 'xlsx' : 'csv'));
    }

    /**
     * Helper to build a descriptive title for the export.
     */
    private function buildExportTitle(array $filters, int $year, array $months): string
    {
        $period = ucfirst($filters['period']);
        $service = $filters['service'];
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

        return "{$period} {$service} Revenue for {$targetName} - {$monthNames} {$year}";
    }
}