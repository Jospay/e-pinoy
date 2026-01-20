<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Revenue;
use Inertia\Inertia;
use Illuminate\Http\Request;

class RevenueManagementController extends Controller
{
    public function index(Request $request)
    {
        $franchiseId = $this->getFranchiseId();
        $timePeriod = $request->input('timePeriod', 'all');

        return Inertia::render('owner/revenue-management/Index', [
            'revenueServiceTypeBreakdownData' => $this->getRevenueBreakdownByServiceType($franchiseId),
            'revenueByPaymentOption' => $this->getRevenueByPaymentOption($franchiseId),
            'revenues' => $this->getPaginatedRevenues($franchiseId, $timePeriod),
            'revenueTrendData' => $this->getRevenueTrendData($franchiseId),
        ]);
    }

    /**
     * Get the authenticated owner's franchise ID or null.
     */
    protected function getFranchiseId(): ?int
    {
        return auth()->user()->ownerDetails?->franchises()->first()?->id;
    }

    /**
     * Get revenue breakdown by service type.
     */
    protected function getRevenueBreakdownByServiceType(?int $franchiseId): array
    {
        return Revenue::where('franchise_id', $franchiseId)
            ->selectRaw('service_type as name, SUM(amount) as total')
            ->groupBy('service_type')
            ->get()
            ->toArray();
    }

    /**
     * Get revenue breakdown by payment option.
     */
    protected function getRevenueByPaymentOption(?int $franchiseId): array
    {
        return Revenue::where('franchise_id', $franchiseId)
            ->join('payment_options', 'revenues.payment_option_id', '=', 'payment_options.id')
            ->selectRaw('payment_options.name, SUM(revenues.amount) as total')
            ->groupBy('payment_options.name')
            ->get()
            ->toArray();
    }

     /**
     * Get paginated revenue list with relationships.
     */
    protected function getPaginatedRevenues(?int $franchiseId, string $timePeriod = 'all')
    {
        $perPage = request('per_page', 10);

        if ($timePeriod === 'daily') {
            return Revenue::when($franchiseId, fn($q) => $q->where('franchise_id', $franchiseId))
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
            return Revenue::when($franchiseId, fn($q) => $q->where('franchise_id', $franchiseId))
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
            return Revenue::when($franchiseId, fn($q) => $q->where('franchise_id', $franchiseId))
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
            return Revenue::when($franchiseId, fn($q) => $q->where('franchise_id', $franchiseId))
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


        $query = Revenue::with(['status', 'franchise', 'paymentOption'])
            ->when($franchiseId, fn($q) => $q->where('franchise_id', $franchiseId))
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
        return $query->when($franchiseId, fn($q) => $q->where('franchise_id', $franchiseId))
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
                'payment_option' => $revenue->paymentOption?->name,
            ]);
    }

    /**
     * Get yearly revenue trend data.
     */
    protected function getRevenueTrendData(?int $franchiseId): array
    {
        return Revenue::where('franchise_id', $franchiseId)
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
