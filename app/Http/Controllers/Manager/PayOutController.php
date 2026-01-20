<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\PercentageType;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayOutController extends Controller
{
    public function index()
    {
        $branch = $this->getBranchOrDefault();
        $branchId = $branch?->id;

        // Total daily gross
        $totalGross = $this->sumRevenueByDate($branchId, today());

        // Dynamic distribution
        $distribution = $this->computeDistribution($totalGross);

        return Inertia::render('manager/payout/Index', [
            'dailyEarnings'   => $totalGross,
            'dailyPayout'     => [
                'date'              => today()->format('Y-m-d'),
                'cutoffTime'        => '12:00 AM',
                'totalGrossFare'    => $totalGross,
                'totalNetPayout'    => $distribution['total'],
                'computedDistribution' => $distribution['items']
            ],
        ]);
    }

    /**
     * Find current branch
     */
    protected function getBranchOrDefault()
    {
        return auth()->user()->managerDetails?->branches()->first();
    }

    /**
     * Sum daily revenue by date for a branch
     */
    protected function sumRevenueByDate(?int $branchId, $date): float
    {
        if (!$branchId) return 0.0;

        return Revenue::where('branch_id', $branchId)
            ->whereHas('status', fn($q) => $q->where('name', 'paid'))
            ->whereDate('created_at', $date)
            ->sum('amount');
    }

    /**
     * Compute ALL percentage & PHP rules dynamically
     */
    protected function computeDistribution(float $totalGross): array
    {
        $rules = PercentageType::all();

        $items = [];
        $total = 0;

        foreach ($rules as $rule) {
            $key = $rule->name . '_amount';

            if ($rule->type === 'Percentage') {
                $amount = ($rule->value / 100) * $totalGross;
            } else {
                $amount = $rule->value;
            }

            $items[$key] = $amount;
            $total += $amount;
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
