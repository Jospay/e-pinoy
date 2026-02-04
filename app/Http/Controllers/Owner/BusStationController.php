<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BusStation;
use App\Models\StationAmount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusStationController extends Controller
{


   public function index(): Response | \Illuminate\Http\RedirectResponse
    {
        $franchise = $this->getFranchiseOrDefault();
        $franchiseId = $franchise?->id;

        // Check if the franchise is active for Bus (Type 2)
        $hasAccess = $franchiseId && \DB::table('franchise_vehicle_type')
            ->where('franchise_id', $franchiseId)
            ->where('vehicle_type_id', 2)
            ->where('status_id', 1)
            ->exists();

        if ($hasAccess) {
            $stations = BusStation::where('franchise_id', $franchiseId)
                ->with(['toAmounts' => function($query) {
                    $query->select('to_bus_station_id', 'amount');
                }])
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($station) {
                    return [
                        'id' => $station->id,
                        'name' => $station->name,
                        'code_no' => $station->code_no,
                        'lat' => (string)$station->latitude,
                        'lng' => (string)$station->longitude,
                        'status_id' => $station->status_id,
                        'amount' => $station->toAmounts->first()?->amount ?? 0,
                    ];
                });

            return Inertia::render('owner/bus-station/Index', [
                'stations' => $stations,
                'franchise_id' => $franchiseId
            ]);
        }

        return redirect()->route('owner.dashboard')->with('error', 'Bus Station access is disabled.');
    }

    protected function getFranchiseOrDefault()
    {
        return auth()->user()->ownerDetails?->franchises()->first();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:bus_stations,name',
            'code_no' => 'required|unique:bus_stations,code_no',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'amount' => 'required|numeric|min:0',
            'franchise_id' => 'required|exists:franchises,id',
            'previous_station_id' => 'nullable|exists:bus_stations,id',
        ]);

        $station = BusStation::create([
            'franchise_id' => $validated['franchise_id'],
            'status_id' => 6,
            'name' => $validated['name'],
            'code_no' => $validated['code_no'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        // Only create fare amount if there is a sequence (Station B, C, etc.)
        if ($validated['previous_station_id']) {
            StationAmount::create([
                'from_bus_station_id' => $validated['previous_station_id'],
                'to_bus_station_id' => $station->id,
                'amount' => $validated['amount'],
            ]);
        }

        return redirect()->back();
    }

    public function update(Request $request, BusStation $busStation)
    {
        $validated = $request->validate([
            'name' => 'required|unique:bus_stations,name,' . $busStation->id,
            'code_no' => 'required|unique:bus_stations,code_no,' . $busStation->id,
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'amount' => 'required|numeric|min:0',
        ]);

        $newStatus = $busStation->status_id == 1 ? 1 : 6;

        $busStation->update([
            'name' => $validated['name'],
            'code_no' => $validated['code_no'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status_id' => $newStatus, // Reset to Pending
        ]);

        $hasPrevious = StationAmount::where('to_bus_station_id', $busStation->id)->first();
        if ($hasPrevious) {
            $hasPrevious->update(['amount' => $validated['amount']]);
        }

        return redirect()->back();
    }
}
