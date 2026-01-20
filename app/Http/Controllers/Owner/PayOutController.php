<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Revenue;
use App\Models\PercentageType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayOutController extends Controller
{
    public function index()
    {
        $franchise = $this->getFranchiseOrDefault();
        $franchiseId = $franchise?->id;

        // Total daily gross
        $totalGross = $this->sumRevenueByDate($franchiseId, today());

        // Dynamic distribution
        $distribution = $this->computeDistribution($totalGross);

        return Inertia::render('owner/payout/Index', [
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
     * Find current franchise
     */
    protected function getFranchiseOrDefault()
    {
        return auth()->user()->ownerDetails?->franchises()->first();
    }

    /**
     * Sum daily revenue by date for a franchise
     */
    protected function sumRevenueByDate(?int $franchiseId, $date): float
    {
        if (!$franchiseId) return 0.0;

        return Revenue::where('franchise_id', $franchiseId)
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
