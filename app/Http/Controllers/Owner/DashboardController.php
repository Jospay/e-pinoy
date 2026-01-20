<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BoundaryContract;
use App\Models\Expense;
use App\Models\Revenue;
use App\Models\UserDriver;
use App\Models\UserTechnician;
use App\Models\Vehicle;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $franchise = $this->getFranchiseOrDefault();
        $franchiseId = $franchise?->id;

        $year = now()->year;

        return Inertia::render('owner/dashboard/Index', [
            'franchiseExists' => (bool) $franchise,


            'activeVehicles' => $this->countVehicles($franchiseId, 1),
            'pendingVehicles' => $this->countVehicles($franchiseId, 6),
            'vehiclesUnderMaintenance' => $this->countVehicles($franchiseId, 5),


            'activeDrivers' => $this->countDrivers($franchiseId, 1),
            'pendingDrivers' => $this->countDrivers($franchiseId, 6),

            'activeTechnicians' => $this->countTechnicians($franchiseId, 1),
            'pendingTechnicians' => $this->countTechnicians($franchiseId, 1),


            'dailyEarnings' => $this->dailyEarnings($franchiseId),
            'yesterdayEarnings' => $this->yesterdayEarnings($franchiseId),


            'dailyTrips' => $this->dailyTrips($franchiseId),
            'yesterdayTrips' => $this->yesterdayTrips($franchiseId),


            'pendingBoundaryDueCount' => $this->countPendingBoundaryContracts($franchiseId),


            'revenueExpensesData' => $this->getRevenueExpensesData($franchiseId, $year),


            'netProfitData' => $this->getNetProfitData($franchiseId, $year, 7),
        ]);
    }

    protected function getFranchiseOrDefault()
    {
        return auth()->user()->ownerDetails?->franchises()->first();
    }

    protected function countVehicles(?int $franchiseId, int $statusId): int
    {
        return $franchiseId
            ? Vehicle::where('franchise_id', $franchiseId)->where('status_id', $statusId)->count()
            : 0;
    }

    protected function countDrivers(?int $franchiseId, int $statusId): int
    {
        return $franchiseId
            ? UserDriver::whereHas('franchises', fn($q) => $q->where('franchise_id', $franchiseId))
                ->where('status_id', $statusId)
                ->count()
            : 0;
    }

    protected function countTechnicians(?int $franchiseId, int $statusId): int
    {
        return $franchiseId
            ? UserTechnician::whereHas('franchises', fn($q) => $q->where('franchise_id', $franchiseId))
                ->where('status_id', $statusId)
                ->count()
            : 0;
    }

    protected function dailyEarnings(?int $franchiseId): float
    {
        return $this->sumRevenueByDate($franchiseId, today());
    }

    protected function yesterdayEarnings(?int $franchiseId): float
    {
        return $this->sumRevenueByDate($franchiseId, Carbon::yesterday());
    }

    protected function dailyTrips(?int $franchiseId): int
    {
        return $this->countTotalPaidTrips($franchiseId, today());
    }

    protected function yesterdayTrips(?int $franchiseId): int
    {
        return $this->countTotalPaidTrips($franchiseId, Carbon::yesterday());
    }

    protected function sumRevenueByDate(?int $franchiseId, $date): float
    {
        if (!$franchiseId) return 0.0;

        return Revenue::where('franchise_id', $franchiseId)
            ->whereHas('status', function ($query) {
                $query->where('name', 'paid');
            })
            ->whereDate('created_at', $date)
            ->sum('amount');
    }

    protected function countPendingBoundaryContracts(?int $franchiseId): int
    {
        if (!$franchiseId) return 0;

        return BoundaryContract::where('franchise_id', $franchiseId)
            ->whereHas('status', fn($q) => $q->where('name', 'pending'))
            ->count();
    }

    protected function countTotalPaidTrips(?int $franchiseId, $date): int
    {
        if (!$franchiseId) return 0;

        return Revenue::where('franchise_id', $franchiseId)
            ->whereHas('status', function ($query) {
                $query->where('name', 'paid');
            })
            ->whereDate('created_at', $date)
            ->where('service_type', 'Trips')
            ->count();
    }

    protected function getRevenueExpensesData(?int $franchiseId, int $year): array
    {
        if (!$franchiseId) return [];

        $revenues = Revenue::where('franchise_id', $franchiseId)
            ->whereYear('payment_date', $year)
            ->selectRaw('DATE(payment_date) as date, SUM(amount) as Revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $expenses = Expense::where('franchise_id', $franchiseId)
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

    protected function getNetProfitData(?int $franchiseId, int $currentYear, int $yearsBack = 7): array
    {
        if (!$franchiseId) return [];

        return collect(range($currentYear - $yearsBack, $currentYear))
            ->map(fn($year) => [
                'year' => $year,
                'Growth Rate' => Revenue::where('franchise_id', $franchiseId)->whereYear('payment_date', $year)->sum('amount')
                    - Expense::where('franchise_id', $franchiseId)->whereYear('payment_date', $year)->sum('amount'),
            ])
            ->toArray();
    }
}
