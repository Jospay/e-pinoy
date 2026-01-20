<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\BoundaryContract;
use App\Models\Expense;
use App\Models\Revenue;
use App\Models\UserDriver;
use App\Models\UserTechnician;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $branch = $this->getBranchOrDefault();
        $branchId = $branch?->id;

        $year = now()->year;

        return Inertia::render('manager/dashboard/Index', [
            'branchExists' => (bool) $branch,


            'activeVehicles' => $this->countVehicles($branchId, 1),
            'pendingVehicles' => $this->countVehicles($branchId, 6),
            'vehiclesUnderMaintenance' => $this->countVehicles($branchId, 5),


            'activeDrivers' => $this->countDrivers($branchId, 1),
            'pendingDrivers' => $this->countDrivers($branchId, 6),

            'activeTechnicians' => $this->countTechnicians($branchId, 1),
            'pendingTechnicians' => $this->countTechnicians($branchId, 1),


            'dailyEarnings' => $this->dailyEarnings($branchId),
            'yesterdayEarnings' => $this->yesterdayEarnings($branchId),


            'dailyTrips' => $this->dailyTrips($branchId),
            'yesterdayTrips' => $this->yesterdayTrips($branchId),


            'pendingBoundaryDueCount' => $this->countPendingBoundaryContracts($branchId),


            'revenueExpensesData' => $this->getRevenueExpensesData($branchId, $year),


            'netProfitData' => $this->getNetProfitData($branchId, $year, 7),
        ]);
    }

    protected function getBranchOrDefault()
    {
        return auth()->user()->managerDetails?->branches()->first();
    }

    protected function countVehicles(?int $branchId, int $statusId): int
    {
        return $branchId
            ? Vehicle::where('branch_id', $branchId)->where('status_id', $statusId)->count()
            : 0;
    }

    protected function countDrivers(?int $branchId, int $statusId): int
    {
        return $branchId
            ? UserDriver::whereHas('branches', fn($q) => $q->where('branch_id', $branchId))
                ->where('status_id', $statusId)
                ->count()
            : 0;
    }

    protected function countTechnicians(?int $branchId, int $statusId): int
    {
        return $branchId
            ? UserTechnician::whereHas('branches', fn($q) => $q->where('branch_id', $branchId))
                ->where('status_id', $statusId)
                ->count()
            : 0;
    }

    protected function dailyEarnings(?int $branchId): float
    {
        return $this->sumRevenueByDate($branchId, today());
    }

    protected function yesterdayEarnings(?int $branchId): float
    {
        return $this->sumRevenueByDate($branchId, Carbon::yesterday());
    }

    protected function dailyTrips(?int $branchId): int
    {
        return $this->countTotalPaidTrips($branchId, today());
    }

    protected function yesterdayTrips(?int $branchId): int
    {
        return $this->countTotalPaidTrips($branchId, Carbon::yesterday());
    }

    protected function sumRevenueByDate(?int $branchId, $date): float
    {
        if (!$branchId) return 0.0;

        return Revenue::where('branch_id', $branchId)
            ->whereHas('status', function ($query) {
                $query->where('name', 'paid');
            })
            ->whereDate('created_at', $date)
            ->sum('amount');
    }

    protected function countPendingBoundaryContracts(?int $branchId): int
    {
        if (!$branchId) return 0;

        return BoundaryContract::where('branch_id', $branchId)
            ->whereHas('status', fn($q) => $q->where('name', 'pending'))
            ->count();
    }

    protected function countTotalPaidTrips(?int $branchId, $date): int
    {
        if (!$branchId) return 0;

        return Revenue::where('branch_id', $branchId)
            ->whereHas('status', function ($query) {
                $query->where('name', 'paid');
            })
            ->whereDate('created_at', $date)
            ->where('service_type', 'Trips')
            ->count();
    }

    protected function getRevenueExpensesData(?int $branchId, int $year): array
    {
        if (!$branchId) return [];

        $revenues = Revenue::where('branch_id', $branchId)
            ->whereYear('payment_date', $year)
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as Revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $expenses = Expense::where('branch_id', $branchId)
            ->whereYear('payment_date', $year)
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as Expenses')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = $revenues->pluck('date')->merge($expenses->pluck('date'))->unique()->sort();

        return $dates->map(fn($date) => [
            'date' => $date,
            'Revenue' => $revenues->firstWhere('date', $date)?->Revenue ?? 0,
            'Expenses' => $expenses->firstWhere('date', $date)?->Expenses ?? 0,
        ])->values()->toArray();
    }

    protected function getNetProfitData(?int $branchId, int $currentYear, int $yearsBack = 7): array
    {
        if (!$branchId) return [];

        return collect(range($currentYear - $yearsBack, $currentYear))
            ->map(fn($year) => [
                'year' => $year,
                'Growth Rate' => Revenue::where('branch_id', $branchId)->whereYear('payment_date', $year)->sum('amount')
                    - Expense::where('branch_id', $branchId)->whereYear('payment_date', $year)->sum('amount'),
            ])
            ->toArray();
    }
}
