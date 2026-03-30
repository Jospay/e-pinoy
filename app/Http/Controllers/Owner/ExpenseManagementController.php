<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseManagementController extends Controller
{
    public function index(Request $request)
    {
        $franchiseId = $this->getFranchiseId();
        $timePeriod = $request->input('timePeriod', 'all');

        return Inertia::render('owner/expense-management/Index', [
            // Changed to a simple total since status_id is removed
            'expenseByPaymentOption' => $this->getGeneralExpenseBreakdown($franchiseId),
            'expenses' => $this->getPaginatedExpense($franchiseId, $timePeriod),
            'expenseTrendData' => $this->getExpenseTrendData($franchiseId),
        ]);
    }

    protected function getFranchiseId(): ?int
    {
        return auth()->user()->ownerDetails?->franchises()->first()?->id;
    }

    /**
     * Updated: Removed status_id join.
     * Returns a simple total for the franchise.
     */
    protected function getGeneralExpenseBreakdown(?int $franchiseId): array
    {
        return Expense::where('franchise_id', $franchiseId)
            ->selectRaw("'Total Expenses' as name, SUM(amount) as total")
            ->groupBy('name')
            ->get()
            ->toArray();
    }

    protected function getPaginatedExpense(?int $franchiseId, string $timePeriod = 'all')
    {
        $perPage = request('per_page', 10);

        // Time Period Views (Removed 'paid' status requirement)
        if (in_array($timePeriod, ['daily', 'weekly', 'monthly', 'yearly'])) {
            $query = Expense::when($franchiseId, fn($q) => $q->where('franchise_id', $franchiseId));

            if ($timePeriod === 'daily') {
                $query->selectRaw("DATE(payment_date) as payment_date, SUM(amount) as total")
                    ->groupBy('payment_date')
                    ->orderByDesc('payment_date');
            } elseif ($timePeriod === 'weekly') {
                $query->selectRaw("YEAR(payment_date) as year, WEEK(payment_date, 1) as week_num, MIN(DATE(payment_date)) as week_start, MAX(DATE(payment_date)) as week_end, SUM(amount) as total")
                    ->groupBy('year', 'week_num')
                    ->orderByDesc('week_start');
            } elseif ($timePeriod === 'monthly') {
                $query->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as month_sort, DATE_FORMAT(payment_date, '%M %Y') as month_name, SUM(amount) as total")
                    ->groupBy('month_sort', 'month_name')
                    ->orderByDesc('month_sort');
            } elseif ($timePeriod === 'yearly') {
                $query->selectRaw("YEAR(payment_date) as year, SUM(amount) as total")
                    ->groupBy('year')
                    ->orderByDesc('year');
            }

            return $query->paginate($perPage)->through(fn($row) => $row->toArray());
        }

        // Standard Detailed View (Removed status relationship)
        $query = Expense::with(['franchise'])
            ->when($franchiseId, fn($q) => $q->where('franchise_id', $franchiseId))
            ->orderByDesc('payment_date');

        return $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn($expense) => [
                'id' => $expense->id,
                'invoice_no' => $expense->invoice_no,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
                'payment_date' => $expense->payment_date,
                'notes' => $expense->notes,
                'franchise' => $expense->franchise?->name,
                // Status and Payment Option removed
            ]);
    }

    protected function getExpenseTrendData(?int $franchiseId): array
    {
        return Expense::where('franchise_id', $franchiseId)
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
