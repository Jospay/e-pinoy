<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\Revenue;
use App\Models\Expense;
use App\Models\UserManager;
use App\Models\UserDriver;
use App\Http\Resources\SuperAdmin\FranchiseDatatableResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the super admin dashboard.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $filters = [
            'status' => $validated['status'] ?? 'active',
        ];

        $today = Carbon::today();
        // Get today's revenues total
        $totalRevenue = Revenue::whereDate('payment_date', $today)
            ->sum('amount');
        // Get today's expenses total
        $totalExpenses = Expense::whereDate('payment_date', $today)
            ->sum('amount');
        // Get total active franchises
        $totalFranchises = Franchise::whereHas('status', function ($query) {
            $query->where('name', 'active');
        })->count();

        $totalDrivers = UserDriver::whereHas('status', function ($query) {
            $query->where('name', 'active');
        })->count();

        $franchises = Franchise::with([
            'owner.user:id,username',
            'status:id,name'
        ])->whereHas('status', fn ($q) => $q->where('name', $filters['status']))->get();

        return Inertia::render('super-admin/dashboard/DashboardIndex', [
            'franchises' => FranchiseDatatableResource::collection($franchises),
            'stats' => [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'total_franchises' => $totalFranchises,
                'total_drivers' => $totalDrivers
            ],
            'filters' => [
                'status' => $filters['status'],
            ]
        ]);
    }
}