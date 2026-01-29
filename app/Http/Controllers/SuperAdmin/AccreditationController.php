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
        $statusInput = $request->input('status', 'active');
        $franchiseFilter = $request->input('franchise', []);
        $vehicleType = $request->input('vehicle_type');

        if (is_string($franchiseFilter)) {
            $franchiseFilter = explode(',', $franchiseFilter);
        }

        $franchiseFilter = array_map(
            'intval',
            array_filter((array) $franchiseFilter)
        );

        $statusId = match ($statusInput) {
            'active' => 1,
            'pending' => 6,
            'deny' => 18,
            default => 1,
        };

        $query = DB::table('franchise_vehicle_type')
            ->join('franchises', 'franchise_vehicle_type.franchise_id', '=', 'franchises.id')
            ->join('vehicle_types', 'franchise_vehicle_type.vehicle_type_id', '=', 'vehicle_types.id')
            ->select(
                'franchise_vehicle_type.franchise_id',
                'franchise_vehicle_type.vehicle_type_id',
                'franchises.name as franchise_name',
                'vehicle_types.name as vehicle_type_name',
                'franchise_vehicle_type.status_id',
                DB::raw("CONCAT(franchise_vehicle_type.franchise_id, '-', franchise_vehicle_type.vehicle_type_id) as id")
            )
            ->where('franchise_vehicle_type.status_id', $statusId);

        if (!empty($franchiseFilter)) {
            $query->whereIn('franchise_vehicle_type.franchise_id', $franchiseFilter);
        }

        if (!empty($vehicleType)) {
            $query->where('vehicle_types.name', $vehicleType);
        }

        $results = $query->get();

        return Inertia::render('super-admin/fleet/Accreditation', [
            'vehicles' => [
                'data' => $results->map(fn ($item) => [
                    'id' => $item->id,
                    'franchise_id' => (int) $item->franchise_id,
                    'vehicle_type_id' => (int) $item->vehicle_type_id,
                    'franchise_name' => $item->franchise_name,
                    'vehicle_type' => $item->vehicle_type_name,
                    'status_id' => (int) $item->status_id,
                    'status_label' => match ($item->status_id) {
                        1 => 'Active',
                        6 => 'Pending',
                        default => 'Deny',
                    },
                ]),
            ],
            'franchises' => Franchise::select('id', 'name')->get(),
            'filters' => [
                'franchise' => $franchiseFilter,
                'status' => $statusInput,
                'vehicle_type' => $vehicleType,
            ],
        ]);
    }

    public function approve(Request $request)
    {
        $request->validate([
            'franchise_id' => 'required|exists:franchises,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        DB::table('franchise_vehicle_type')
            ->where('franchise_id', $request->franchise_id)
            ->where('vehicle_type_id', $request->vehicle_type_id)
            ->update(['status_id' => 1]);

        return back()->with('success', 'Accreditation approved successfully');
    }

    public function decline(Request $request)
    {
        $request->validate([
            'franchise_id' => 'required|exists:franchises,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
        ]);

        DB::table('franchise_vehicle_type')
            ->where('franchise_id', $request->franchise_id)
            ->where('vehicle_type_id', $request->vehicle_type_id)
            ->update(['status_id' => 18]);

        return back()->with('success', 'Accreditation declined successfully');
    }
}
