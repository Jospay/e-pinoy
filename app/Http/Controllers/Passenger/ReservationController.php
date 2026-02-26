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
        $fromStationId = $request->query('from_id');
        $origin = BusStation::findOrFail($fromStationId);

        // Passengers can only book to other ACTIVE stations within the SAME franchise
        $destinations = BusStation::where('franchise_id', $origin->franchise_id)
            ->where('id', '!=', $fromStationId)
            ->where('status_id', 1)
            ->get();

        return Inertia::render('passenger/dashboard/Reserve', [
            'origin' => $origin,
            'destinations' => $destinations,
        ]);
    }
}
