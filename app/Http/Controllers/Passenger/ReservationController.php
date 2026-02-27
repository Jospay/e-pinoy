<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\BusStation;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        // 1. Find Franchise IDs that have AT LEAST 2 active stations
        $validFranchiseIds = BusStation::where('status_id', 1) // Must be Active
            ->select('franchise_id', DB::raw('count(*) as total'))
            ->groupBy('franchise_id')
            ->having('total', '>=', 2)
            ->pluck('franchise_id');

        // 2. Fetch those stations
        $stations = BusStation::whereIn('franchise_id', $validFranchiseIds)
            ->where('status_id', 1) // Double check they are active
            ->orderBy('id', 'asc')
            ->get()
            ->map(function($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code_no,
                    'lat' => (float)$s->latitude,
                    'lng' => (float)$s->longitude,
                    // Cached geocoding to fix "Address not found"
                    'address' => $this->getReverseGeocode($s->latitude, $s->longitude),
                ];
            });

        return Inertia::render('passenger/dashboard/Index', [
            'stations' => $stations
        ]);
    }

    private function getReverseGeocode($lat, $lng)
    {
        $cacheKey = "addr_v5_{$lat}_{$lng}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lng) {
            try {
                usleep(1100000); // 1.1s delay to respect API limits

                $response = Http::withHeaders([
                    'User-Agent' => 'BusApp_Terminal_System_v2'
                ])
                ->timeout(5)
                ->get("https://nominatim.openstreetmap.org/reverse", [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                ]);

                $data = $response->json();
                return $data['display_name'] ?? "Location: $lat, $lng";

            } catch (\Exception $e) {
                return 'Address detail loading...';
            }
        });
    }

    public function create(Request $request)
{
    $fromId = $request->query('from_id');
    $origin = BusStation::findOrFail($fromId);

    // Get all active destinations in the same franchise
    $destinations = BusStation::where('franchise_id', $origin->franchise_id)
        ->where('id', '!=', $fromId)
        ->where('status_id', 1)
        ->orderBy('id', 'asc')
        ->get()
        ->map(function($dest) use ($fromId) {
            // Logic: Sum the amounts of all stations between From and To
            // This assumes stations are ordered by ID as they were created
            $totalAmount = \App\Models\StationAmount::where('to_bus_station_id', '>', min($fromId, $dest->id))
                ->where('to_bus_station_id', '<=', max($fromId, $dest->id))
                // Only sum amounts belonging to this franchise's sequence
                ->whereHas('toStation', function($q) use ($dest) {
                    $q->where('franchise_id', $dest->franchise_id);
                })
                ->sum('amount');

            return [
                'id' => $dest->id,
                'name' => $dest->name,
                'code' => $dest->code_no,
                'calculated_fare' => (float)($totalAmount > 0 ? $totalAmount : 15.0), // Fallback to base fare
            ];
        });

    return Inertia::render('passenger/dashboard/Reserve', [
        'origin' => [
            'id' => $origin->id,
            'name' => $origin->name,
            'code' => $origin->code_no,
            'lat' => (float)$origin->latitude,
            'lng' => (float)$origin->longitude,
        ],
        'destinations' => $destinations,
    ]);
}
}
