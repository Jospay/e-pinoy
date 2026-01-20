<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class FranchiseContractController extends Controller
{
    public function index()
    {
        $data = [
            'day' => now()->format('d'),
            'month' => now()->format('F'),
            'year' => now()->format('Y'),

            'corporation_name' => 'DDGNS (DEVICE DESIGN GREEN AND SMART CORPORATION)',
            'corporation_short' => 'DDGNS',

            'franchise_owner_name' => 'JUAN DELA CRUZ',
            'franchise_name' => 'Franchise Name Sample',
            'operator_address' => 'Baguio City, Philippines',

            'vehicle_count' => 20,
            'security_days' => 60,
            'daily_boundary' => 1600,
        ];

        return Pdf::loadView('contracts.master', $data)
            ->setPaper('a4')
            ->stream('electric-taxi-agreement.pdf');
    }
}
