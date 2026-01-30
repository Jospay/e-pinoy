<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BusStation;
use App\Models\StationAmount;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class BusStationController extends Controller
{
    public function index(): Response
    {
        // For this example, I'm assuming the owner belongs to one franchise
        // Adjust the 'franchise_id' logic based on your Auth system
        $franchiseId = 1;

        $stations = BusStation::where('franchise_id', $franchiseId)
            ->with(['toAmounts' => function($query) {
                // This gets the amount where THIS station is the 'second' station (the fare to get here)
                $query->select('second_bus_station_id', 'amount');
            }])
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($station) {
                return [
                    'id' => $station->id,
                    'name' => $station->name,
                    'code_no' => $station->code_no,
                    'lat' => $station->latitude,
                    'lng' => $station->longitude,
                    // Map the amount from the pivot-like relationship
                    'amount' => $station->toAmounts->first()?->amount ?? 0,
                ];
            });

        return Inertia::render('owner/bus-station/Index', [
            'stations' => $stations,
            'franchise_id' => $franchiseId
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:bus_stations,name',
            'code_no' => 'required|unique:bus_stations,code_no',
            // Latitude max is 90, Longitude max is 180
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'amount' => 'required|numeric|min:0',
            'franchise_id' => 'required|exists:franchises,id',
            'previous_station_id' => 'nullable|exists:bus_stations,id',
        ]);

        $station = BusStation::create([
            'franchise_id' => $validated['franchise_id'],
            'status_id' => 1,
            'name' => $validated['name'],
            'code_no' => $validated['code_no'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        if ($request->previous_station_id) {
            StationAmount::create([
                'first_bus_station_id' => $request->previous_station_id,
                'second_bus_station_id' => $station->id,
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

        // Update Station Info
        $busStation->update([
            'name' => $validated['name'],
            'code_no' => $validated['code_no'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        // Update the Fare (Amount to get TO this station)
        // We look for the record where this station is the 'second' point in the leg
        StationAmount::where('second_bus_station_id', $busStation->id)
            ->update(['amount' => $validated['amount']]);

        return redirect()->back();
    }
}
