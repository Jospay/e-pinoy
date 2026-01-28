<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AccreditationController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get inputs
        $statusInput = $request->input('status', 'active');
        $franchiseFilter = $request->input('franchise', []);

        // 2. Ensure franchise is an array and convert to integers
        if (is_string($franchiseFilter)) {
            $franchiseFilter = explode(',', $franchiseFilter);
        }
        $franchiseFilter = array_map('intval', array_filter((array)$franchiseFilter));

        // 3. Determine status ID
        $statusId = ($statusInput === 'active') ? 1 : 6;

        // 4. Build query
        $query = DB::table('franchise_vehicle_type')
            ->join('franchises', 'franchise_vehicle_type.franchise_id', '=', 'franchises.id')
            ->join('vehicle_types', 'franchise_vehicle_type.vehicle_type_id', '=', 'vehicle_types.id')
            ->select(
                'franchises.name as franchise_name',
                'vehicle_types.name as vehicle_type_name',
                'franchise_vehicle_type.status_id',
                'franchise_vehicle_type.franchise_id',
                'franchise_vehicle_type.vehicle_type_id',
                DB::raw("CONCAT(franchise_id, '-', vehicle_type_id) as id")
            )
            ->where('franchise_vehicle_type.status_id', $statusId);

        // 5. Apply franchise filter if provided
        if (!empty($franchiseFilter)) {
            $query->whereIn('franchise_vehicle_type.franchise_id', $franchiseFilter);
        }

        // 6. Execute query
        $results = $query->get();

        // 7. Log for debugging (remove in production)
        \Log::info('Accreditation Filter', [
            'status_input' => $statusInput,
            'status_id' => $statusId,
            'franchise_filter' => $franchiseFilter,
            'result_count' => $results->count()
        ]);

        // 8. Return Inertia response
        return Inertia::render('super-admin/fleet/Accreditation', [
            'vehicles' => [
                'data' => $results->map(fn($item) => [
                    'id' => $item->id,
                    'franchise_name' => $item->franchise_name,
                    'vehicle_type' => $item->vehicle_type_name,
                    'status_id' => (int)$item->status_id,
                    'status_label' => $item->status_id == 1 ? 'Active' : 'Pending',
                ])
            ],
            'franchises' => Franchise::select('id', 'name')->get(),
            'filters' => [
                'franchise' => $franchiseFilter,
                'status' => $statusInput,
            ],
        ]);
    }
}
