<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\BusStation; // Import the model
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class StationManagementController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get Filters
        $statusInput = $request->input('status', 'active'); // Match your seeder (Status 1)
        $franchiseFilter = $request->input('franchise', []);
        $activeTab = $request->input('vehicle_type', 'Bus'); // Default to Bus to see seeder data

        $statusId = match ($statusInput) {
            'active' => 1,
            'pending' => 6,
            'deny' => 18,
            default => 1,
        };

       // Inside the index method, update the select and groupBy
        $query = DB::table('bus_stations')
            ->join('franchises', 'bus_stations.franchise_id', '=', 'franchises.id')
            ->leftJoin('station_amount', 'bus_stations.id', '=', 'station_amount.first_bus_station_id')
            ->select(
                'bus_stations.id',
                'bus_stations.name',
                'bus_stations.code_no',
                'bus_stations.status_id',
                'bus_stations.latitude',
                'bus_stations.longitude',
                'bus_stations.franchise_id',
                'franchises.name as franchise_name',
                DB::raw('count(station_amount.id) as total_connections')
            )
            ->where('bus_stations.status_id', $statusId)
            ->groupBy(
                'bus_stations.id',
                'bus_stations.name',
                'bus_stations.code_no',
                'bus_stations.status_id',
                'bus_stations.latitude',
                'bus_stations.longitude',
                'bus_stations.franchise_id',
                'franchises.name'
            );

        // 3. Apply Franchise Filter
        if (!empty($franchiseFilter)) {
            $franchiseIds = is_array($franchiseFilter) ? $franchiseFilter : explode(',', $franchiseFilter);
            $query->whereIn('bus_stations.franchise_id', $franchiseIds);
        }

        $results = $query->get();

        return Inertia::render('super-admin/fleet/StationManagement', [
           'stations' => [
                'data' => $results->map(fn ($item) => [
                    'id' => $item->id,
                    'station_name' => $item->name, // We will keep this as 'station_name'
                    'code_no' => $item->code_no,
                    'franchise_name' => $item->franchise_name,
                    'connections' => $item->total_connections,
                    'latitude' => $item->latitude,   // Added this
                    'longitude' => $item->longitude, // Added this
                    'status_id' => (int) $item->status_id,
                    'status_label' => match ($item->status_id) {
                        1 => 'Active',
                        6 => 'Pending',
                        18 => 'Denied',
                        default => 'Active',
                    },
                ]),
            ],
            'franchises' => Franchise::select('id', 'name')->get(),
            'filters' => [
                'franchise' => $franchiseFilter,
                'status' => $statusInput,
                'vehicle_type' => $activeTab,
            ],
        ]);
    }

    public function approve(Request $request)
    {
        $request->validate(['id' => 'required|exists:bus_stations,id']);

        DB::table('bus_stations')
            ->where('id', $request->id)
            ->update(['status_id' => 1]);

        return back()->with('success', 'Station approved successfully');
    }

    public function decline(Request $request)
    {
        $request->validate(['id' => 'required|exists:bus_stations,id']);

        DB::table('bus_stations')
            ->where('id', $request->id)
            ->update(['status_id' => 18]);

        return back()->with('success', 'Station denied successfully');
    }
}
