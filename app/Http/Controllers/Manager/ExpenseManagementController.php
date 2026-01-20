<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseManagementController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->getBranchId();
        $timePeriod = $request->input('timePeriod', 'all');

        return Inertia::render('manager/expense-management/Index', [
            // 'expenseTypeBreakdownData' => $this->getExpenseBreakdownByExpenseType($branchId),
            'expenseByPaymentOption' => $this->getExpenseByPaymentOption($branchId),
            'expenses' => $this->getPaginatedExpense($branchId, $timePeriod),
            'expenseTrendData' => $this->getExpenseTrendData($branchId),
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
     * Get expense breakdown by expense type.
     */
    // protected function getExpenseBreakdownByExpenseType(?int $branchId): array
    // {
    //     return Expense::where('branch_id', $branchId)
    //         ->selectRaw('expense_type as name, SUM(amount) as total')
    //         ->groupBy('expense_type')
    //         ->get()
    //         ->toArray();
    // }

    /**
     * Get expense breakdown by payment option.
     */
    protected function getExpenseByPaymentOption(?int $branchId): array
    {
        return Expense::where('branch_id', $branchId)
            ->join('payment_options', 'expenses.payment_option_id', '=', 'payment_options.id')
            ->selectRaw('payment_options.name, SUM(expenses.amount) as total')
            ->groupBy('payment_options.name')
            ->get()
            ->toArray();
    }

    /**
     * Get paginated expenses list with relationships.
     */
    protected function getPaginatedExpense(?int $branchId, string $timePeriod = 'all')
    {
        $perPage = request('per_page', 10);

        if ($timePeriod === 'daily') {
            return Expense::when($branchId, fn($q) => $q->where('branch_id', $branchId))
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
            return Expense::when($branchId, fn($q) => $q->where('branch_id', $branchId))
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
            return Expense::when($branchId, fn($q) => $q->where('branch_id', $branchId))
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
            return Expense::when($branchId, fn($q) => $q->where('branch_id', $branchId))
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

        $query = Expense::with(['status', 'franchise', 'branch', 'paymentOption'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
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
        return $query->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn($expense) => [
                'id' => $expense->id,
                'invoice_no' => $expense->invoice_no,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
                // 'expense_type' => $expense->expense_type,
                'payment_date' => $expense->payment_date,
                'notes' => $expense->notes,
                'status' => $expense->status?->name,
                'franchise' => $expense->franchise?->name,
                'branch' => $expense->branch?->name,
                'payment_option' => $expense->paymentOption?->name,
            ]);
    }

    /**
     * Get yearly expense trend data.
     */
    protected function getExpenseTrendData(?int $branchId): array
    {
        return Expense::where('branch_id', $branchId)
            ->selectRaw('YEAR(payment_date) as year, SUM(amount) as expense')
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(fn($item) => [
                'year' => (int) $item->year,
                'expense' => (float) $item->expense,
            ])
            ->toArray();
    }
}
