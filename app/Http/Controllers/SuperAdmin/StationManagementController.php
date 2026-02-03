<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\BusStation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class StationManagementController extends Controller
{
    public function index(Request $request)
    {
        $statusInput = $request->input('status', 'active');
        $franchiseFilter = $request->input('franchise', []);
        $activeTab = $request->input('vehicle_type', 'Bus');

        $statusId = match ($statusInput) {
            'active' => 1,
            'pending' => 6,
            'deny' => 18,
            default => 1,
        };

        $query = DB::table('bus_stations')
            ->join('franchises', 'bus_stations.franchise_id', '=', 'franchises.id')
            // Logic: Join station_amount where this station is EITHER the first or second point
            ->leftJoin('station_amount', function($join) {
                $join->on('bus_stations.id', '=', 'station_amount.first_bus_station_id')
                     ->orOn('bus_stations.id', '=', 'station_amount.second_bus_station_id');
            })
            // Join the "Other" station to get the name of the connection
            ->leftJoin('bus_stations as related_station', function($join) {
                $join->on(DB::raw('CASE
                    WHEN bus_stations.id = station_amount.first_bus_station_id THEN station_amount.second_bus_station_id
                    ELSE station_amount.first_bus_station_id
                END'), '=', 'related_station.id');
            })
            ->select(
                'bus_stations.id',
                'bus_stations.name',
                'bus_stations.code_no',
                'bus_stations.status_id',
                'bus_stations.latitude',
                'bus_stations.longitude',
                'bus_stations.franchise_id',
                'franchises.name as franchise_name',
                DB::raw('GROUP_CONCAT(
                    IF(related_station.name IS NULL, NULL,
                        JSON_OBJECT(
                            "station_name", related_station.name,
                            "amount", IFNULL(station_amount.amount, 0),
                            "type", IF(bus_stations.id = station_amount.first_bus_station_id, "outbound", "inbound")
                        )
                    )
                ) as routes_raw'),
                DB::raw('count(related_station.id) as total_connections')
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

        if (!empty($franchiseFilter)) {
            $franchiseIds = is_array($franchiseFilter) ? $franchiseFilter : explode(',', $franchiseFilter);
            $query->whereIn('bus_stations.franchise_id', $franchiseIds);
        }

        $results = $query->get();

        return Inertia::render('super-admin/fleet/StationManagement', [
            'stations' => [
                'data' => $results->map(function ($item) {
                    $routes = [];
                    if ($item->routes_raw) {
                        $decoded = json_decode('[' . $item->routes_raw . ']');
                        $routes = collect($decoded)->filter(fn($r) => !empty($r->station_name))->values();
                    }

                    return [
                        'id' => $item->id,
                        'station_name' => $item->name,
                        'code_no' => $item->code_no,
                        'franchise_name' => $item->franchise_name,
                        'connections' => $item->total_connections,
                        'routes' => $routes,
                        'latitude' => $item->latitude,
                        'longitude' => $item->longitude,
                        'status_id' => (int) $item->status_id,
                        'status_label' => match ((int) $item->status_id) {
                            1 => 'Active',
                            6 => 'Pending',
                            18 => 'Denied',
                            default => 'Active',
                        },
                    ];
                }),
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
        DB::table('bus_stations')->where('id', $request->id)->update(['status_id' => 1]);
        return back()->with('success', 'Station approved successfully');
    }

    public function decline(Request $request)
    {
        $request->validate(['id' => 'required|exists:bus_stations,id']);
        DB::table('bus_stations')->where('id', $request->id)->update(['status_id' => 18]);
        return back()->with('success', 'Station denied successfully');
    }
}
