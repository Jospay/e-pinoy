<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ExpenseManagementController extends Controller
{
    public function index(Request $request)
    {
        $franchiseId = $this->getFranchiseId();
        $timePeriod = $request->input('timePeriod', 'all');
        $branchFilter = $request->input('branch');

        $branches = Branch::where('franchise_id', $franchiseId)->get(['id', 'name']);

        return Inertia::render('owner/expense-management/Index', [
            'branches' => $branches,
            'expenseByPaymentOption' => $this->getGeneralExpenseBreakdown($franchiseId),
            'expenses' => $this->getPaginatedExpense($franchiseId, $timePeriod, $branchFilter),
            'expenseTrendData' => $this->getExpenseTrendData($franchiseId),
        ]);
    }

    protected function getFranchiseId(): ?int
    {
        return auth()->user()->ownerDetails?->franchises()->first()?->id;
    }

    protected function getGeneralExpenseBreakdown(?int $franchiseId): array
    {
        return Expense::where('franchise_id', $franchiseId)
            ->selectRaw("'Total Expenses' as name, SUM(amount) as total")
            ->groupBy('name')
            ->get()
            ->toArray();
    }

    protected function getPaginatedExpense(?int $franchiseId, string $timePeriod = 'all', $branchFilter = 'all')
    {
        $perPage = request('per_page', 10);
        $search = request('search');

        $query = Expense::with(['branch'])
            ->when($franchiseId, fn($q) => $q->where('franchise_id', $franchiseId));

        if ($branchFilter !== 'all' && $branchFilter !== null) {
            if ($branchFilter === 'franchise') {
                $query->whereNull('branch_id');
            } elseif ($branchFilter === 'only_branches') {
                $query->whereNotNull('branch_id');
            } else {
                $query->where('branch_id', $branchFilter);
            }
        }

        // Grouped Views
        if (in_array($timePeriod, ['daily', 'weekly', 'monthly', 'yearly'])) {
            if ($timePeriod === 'daily') {
                $query->selectRaw("DATE(payment_date) as date_label, SUM(amount) as total")
                    ->groupBy('date_label')->orderByDesc('date_label');
            } elseif ($timePeriod === 'weekly') {
                $query->selectRaw("YEARWEEK(payment_date, 1) as year_week, MIN(DATE(payment_date)) as week_start, MAX(DATE(payment_date)) as week_end, SUM(amount) as total")
                    ->groupBy('year_week')->orderByDesc('week_start');
            } elseif ($timePeriod === 'monthly') {
                $query->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as month_sort, DATE_FORMAT(payment_date, '%M %Y') as month_name, SUM(amount) as total")
                    ->groupBy('month_sort', 'month_name')->orderByDesc('month_sort');
            } elseif ($timePeriod === 'yearly') {
                $query->selectRaw("YEAR(payment_date) as year_label, SUM(amount) as total")
                    ->groupBy('year_label')->orderByDesc('year_label');
            }

            return $query->paginate($perPage)->through(fn($row) => [
                'display_label' => match($timePeriod) {
                    'daily' => Carbon::parse($row->date_label)->format('M d, Y'),
                    'weekly' => Carbon::parse($row->week_start)->format('M d') . ' - ' . Carbon::parse($row->week_end)->format('M d, Y'),
                    'monthly' => $row->month_name,
                    'yearly' => $row->year_label,
                    default => ''
                },
                'total' => (float) $row->total
            ]);
        }

        // Detailed View
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn($expense) => [
                'id' => $expense->id,
                'invoice_no' => $expense->invoice_no,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
                'payment_date' => $expense->payment_date,
                'notes' => $expense->notes,
                'created_at' => Carbon::parse($expense->created_at)->format('M d, Y'),
                'branch_id' => $expense->branch_id,
                'branch_name' => $expense->branch_id ? ($expense->branch?->name ?? 'Unknown Branch') : 'Main Franchise',
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
