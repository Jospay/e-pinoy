<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BusStation;
use App\Models\StationAmount;
use App\Models\StationSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BusStationController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get Franchise and Access Check
        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        $franchiseId = $franchise?->id;

        $hasAccess = $franchiseId && DB::table('franchise_vehicle_type')
            ->where(['franchise_id' => $franchiseId, 'vehicle_type_id' => 2, 'status_id' => 1])
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('owner.dashboard')->with('error', 'Access disabled.');
        }

        // 2. Fetch Stations for the Franchise
        $stationsQuery = BusStation::where('franchise_id', $franchiseId)
            ->with(['schedules', 'toAmounts'])
            ->orderBy('id', 'asc')
            ->get();

        $stations = $stationsQuery->map(function($s) {
            $amountRecord = $s->toAmounts->first();

            return [
                'id' => $s->id,
                'name' => $s->name,
                'code_no' => $s->code_no,
                'lat' => (string)$s->latitude,
                'lng' => (string)$s->longitude,
                'status_id' => (int)$s->status_id,
                'amount' => $amountRecord?->amount ?? 0,
                'station_amount_id' => $amountRecord?->id ?? null,
                'schedules' => $s->schedules->map(function($sched) {
                    return [
                        'id' => $sched->id,
                        'bus_station_id' => $sched->bus_station_id,
                        'to_time' => date('H:i', strtotime($sched->to_time)),
                        'from_time' => date('H:i', strtotime($sched->from_time)),
                    ];
                })->toArray(),
            ];
        });

        // 3. Fetch Reservations for the Franchise
        // We identify reservations belonging to this franchise via the from_bus_station_id
        $stationIds = $stationsQuery->pluck('id');
        $filter = $request->query('status', 'completed');

        $transactions = \App\Models\Reservation::with(['fromStation', 'toStation', 'status', 'passenger.user'])
            ->whereIn('from_bus_station_id', $stationIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $statusName = $item->status->name ?? 'Pending';
                $lowerStatus = strtolower($statusName);

                // Logic to categorize status for the frontend UI
                $isCompleted = str_contains($lowerStatus, 'completed');
                $isPaid = str_contains($lowerStatus, 'paid') && !$isCompleted;
                $isPending = !$isPaid && !$isCompleted;

                return [
                    'id' => $item->id,
                    'passenger_name' => $item->passenger?->user?->name ?? 'Guest User',
                    'origin' => $item->fromStation->name ?? 'N/A',
                    'destination' => $item->toStation->name ?? 'N/A',
                    'amount' => number_format($item->amount, 2),
                    'date' => $item->reserve_date,
                    'time_window' => date('h:i A', strtotime($item->reserve_from_time)) . ' - ' . date('h:i A', strtotime($item->reserve_to_time)),
                    'status_text' => $statusName,
                    'is_paid' => $isPaid,
                    'is_pending' => $isPending,
                    'is_completed' => $isCompleted,
                    'booked_at' => $item->created_at->format('M d, Y'),
                ];
            });

        // 4. Return to Inertia
        return Inertia::render('owner/bus-station/Index', [
            'stations' => $stations,
            'franchise_id' => $franchiseId,
            'transactions' => $transactions,
            'initialFilter' => $filter,
            'activeTab' => $request->query('tab', 'stations')
        ]);
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'bus_station_id' => 'required|exists:bus_stations,id',
            'from_time' => 'required',
            'to_time' => 'required',
        ]);

        // Native PHP comparison: Convert "HH:mm" to integer for easy comparison
        $arrive = (int) str_replace(':', '', $validated['to_time']);
        $depart = (int) str_replace(':', '', $validated['from_time']);

        if ($arrive > $depart) {
            return redirect()->back()->withErrors(['to_time' => 'Arrival must be before departure.']);
        }

        $exists = StationSchedule::where('bus_station_id', $validated['bus_station_id'])
            ->where(function($query) use ($validated) {
                $query->where('from_time', '<', $validated['from_time'])
                      ->where('to_time', '>', $validated['to_time']);
            })->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['from_time' => 'This time slot overlaps with an existing schedule.']);
        }

        StationSchedule::create($validated);
        return redirect()->back()->with('success', 'Station time added.');
    }

    public function updateSchedule(Request $request, StationSchedule $schedule)
    {
        $validated = $request->validate([
            'from_time' => 'required',
            'to_time' => 'required',
        ]);

        $arrive = (int) str_replace(':', '', $validated['to_time']);
        $depart = (int) str_replace(':', '', $validated['from_time']);

        if ($arrive > $depart) {
            return redirect()->back()->withErrors(['to_time' => 'Arrival must be before departure.']);
        }

        $exists = StationSchedule::where('bus_station_id', $schedule->bus_station_id)
            ->where('id', '!=', $schedule->id)
            ->where(function($query) use ($validated) {
                $query->where('from_time', '<', $validated['from_time'])
                      ->where('to_time', '>', $validated['to_time']);
            })->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['from_time' => 'This time slot overlaps with another.']);
        }

        $schedule->update($validated);
        return redirect()->back()->with('success', 'Station time updated.');
    }

    public function deleteSchedule(StationSchedule $schedule)
    {
        $schedule->delete();
        return redirect()->back()->with('success', 'Schedule deleted.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:bus_stations,name',
            'code_no' => 'required|unique:bus_stations,code_no',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
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

        if ($validated['previous_station_id']) {
            StationAmount::create([
                'from_bus_station_id' => $validated['previous_station_id'],
                'to_bus_station_id' => $station->id,
                'amount' => $validated['amount'],
            ]);
        }

        return redirect()->back()->with('success', 'Station created.');
    }

    public function update(Request $request, BusStation $busStation)
    {
        $validated = $request->validate([
            'name' => 'required|unique:bus_stations,name,' . $busStation->id,
            'code_no' => 'required|unique:bus_stations,code_no,' . $busStation->id,
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'amount' => 'required|numeric|min:0',
        ]);

        $newStatus = $busStation->status_id == 1 ? 1 : 6;

        $busStation->update([
            'name' => $validated['name'],
            'code_no' => $validated['code_no'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status_id' => $newStatus,
        ]);

        $hasPrevious = StationAmount::where('to_bus_station_id', $busStation->id)->first();
        if ($hasPrevious) {
            $hasPrevious->update(['amount' => $validated['amount']]);
        }

        return redirect()->back()->with('success', 'Station updated.');
    }
}
