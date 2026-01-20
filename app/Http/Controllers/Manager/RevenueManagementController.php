<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RevenueManagementController extends Controller
{
    public function index(Request $request)
    {
        $brandId = $this->getBranchId();
        $timePeriod = $request->input('timePeriod', 'all');

        return Inertia::render('manager/revenue-management/Index', [
            'revenueServiceTypeBreakdownData' => $this->getRevenueBreakdownByServiceType($brandId),
            'revenueByPaymentOption' => $this->getRevenueByPaymentOption($brandId),
            'revenues' => $this->getPaginatedRevenues($brandId, $timePeriod),
            'revenueTrendData' => $this->getRevenueTrendData($brandId),
        ]);
    }

    /**
     * Get the authenticated manager's branch ID or null.
     */
    protected function getBranchId(): ?int
    {
        return auth()->user()->managerDetails?->branches()->first()?->id;
    }

    /**
     * Get revenue breakdown by service type.
     */
    protected function getRevenueBreakdownByServiceType(?int $brandId): array
    {
        return Revenue::where('branch_id', $brandId)
            ->selectRaw('service_type as name, SUM(amount) as total')
            ->groupBy('service_type')
            ->get()
            ->toArray();
    }

    /**
     * Get revenue breakdown by payment option.
     */
    protected function getRevenueByPaymentOption(?int $brandId): array
    {
        return Revenue::where('branch_id', $brandId)
            ->join('payment_options', 'revenues.payment_option_id', '=', 'payment_options.id')
            ->selectRaw('payment_options.name, SUM(revenues.amount) as total')
            ->groupBy('payment_options.name')
            ->get()
            ->toArray();
    }

     /**
     * Get paginated revenue list with relationships.
     */
    protected function getPaginatedRevenues(?int $brandId, string $timePeriod = 'all')
    {
        $perPage = request('per_page', 10);

        if ($timePeriod === 'daily') {
            return Revenue::when($brandId, fn($q) => $q->where('branch_id', $brandId))
                ->where('service_type', 'Trips')
                ->selectRaw("DATE(payment_date) as payment_date, SUM(amount) as total")
                ->whereHas('status', function ($query) {
                    $query->where('name', 'paid');
                })
                ->groupBy('payment_date')
                ->orderByDesc('payment_date')
                ->paginate($perPage)
                ->through(fn($row) => [
                    'payment_date' => $row->payment_date,
                    'total' => $row->total,
                ]);
        }

        if ($timePeriod === 'weekly') {
            return Revenue::when($brandId, fn($q) => $q->where('branch_id', $brandId))
                ->where('service_type', 'Trips')
                ->selectRaw("
                    YEAR(payment_date) as year,
                    WEEK(payment_date, 1) as week_num,
                    MIN(DATE(payment_date)) as week_start,
                    MAX(DATE(payment_date)) as week_end,
                    SUM(amount) as total
                ")
                ->whereRelation('status', 'name', 'paid')
                ->groupBy('year', 'week_num')
                ->orderByDesc('week_start')
                ->paginate($perPage)
                ->through(fn($row) => [
                    'week_start' => $row->week_start,
                    'week_end'   => $row->week_end,
                    'total'      => $row->total,
                ]);
        }

        if ($timePeriod === 'monthly') {
            return Revenue::when($brandId, fn($q) => $q->where('branch_id', $brandId))
                ->where('service_type', 'Trips')
                ->selectRaw("
                    DATE_FORMAT(payment_date, '%Y-%m') as month_sort,
                    DATE_FORMAT(payment_date, '%M %Y') as month_name,
                    SUM(amount) as total
                ")
                ->whereRelation('status', 'name', 'paid')
                ->groupBy('month_sort', 'month_name')
                ->orderByDesc('month_sort')
                ->paginate($perPage)
                ->through(fn($row) => [
                    'month_name' => $row->month_name,
                    'month_sort' => $row->month_sort,
                    'total' => $row->total,
                ]);
        }

        if ($timePeriod === 'yearly') {
            return Revenue::when($brandId, fn($q) => $q->where('branch_id', $brandId))
                ->where('service_type', 'Trips')
                ->selectRaw("YEAR(payment_date) as year, SUM(amount) as total")
                ->whereRelation('status', 'name', 'paid')
                ->groupBy('year')
                ->orderByDesc('year')
                ->paginate($perPage)
                ->through(fn($row) => [
                    'year' => $row->year,
                    'total' => $row->total,
                ]);
        }


        $query = Revenue::with(['status', 'franchise', 'branch', 'paymentOption'])
            ->when($brandId, fn($q) => $q->where('branch_id', $brandId))
            ->where('service_type', 'Trips')
            ->orderByDesc('payment_date');

        /**
         * Status Filter
         */
        if ($status = request('status')) {
            if ($status !== 'all') {
                $query->whereHas('status', fn($q) => $q->where('name', $status));
            }
        }

        /*
         *Payment Option Filter
         */
        if ($paymentOption = request('paymentOption')) {
            if ($paymentOption !== 'all') {
                $query->whereHas('paymentOption', fn($q) => $q->where('name', $paymentOption));
            }
        }

        /**
         * Regular paginated detailed invoices
         */
        return $query->when($brandId, fn($q) => $q->where('branch_id', $brandId))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn($revenue) => [
                'id' => $revenue->id,
                'invoice_no' => $revenue->invoice_no,
                'amount' => $revenue->amount,
                'currency' => $revenue->currency,
                'service_type' => $revenue->service_type,
                'payment_date' => $revenue->payment_date,
                'notes' => $revenue->notes,
                'status' => $revenue->status?->name,
                'franchise' => $revenue->franchise?->name,
                'branch' => $revenue->branch?->name,
                'payment_option' => $revenue->paymentOption?->name,
            ]);
    }

    /**
     * Get yearly revenue trend data.
     */
    protected function getRevenueTrendData(?int $brandId): array
    {
        return Revenue::where('branch_id', $brandId)
            ->selectRaw('YEAR(payment_date) as year, SUM(amount) as revenue')
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(fn($item) => [
                'year' => (int) $item->year,
                'revenue' => (float) $item->revenue,
            ])
            ->toArray();
    }
}
