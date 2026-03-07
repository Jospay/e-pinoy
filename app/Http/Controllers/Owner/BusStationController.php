<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BusStation;
use App\Models\StationAmount;
use App\Models\StationSchedule;
use App\Models\DaySchedule;
use App\Models\Vehicle;
use App\Models\Reservation;
use App\Models\DateSchedule;
use App\Models\StationReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BusStationController extends Controller
{
    public function index(Request $request)
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        $franchiseId = $franchise?->id;

        $hasAccess = $franchiseId && DB::table('franchise_vehicle_type')
            ->where(['franchise_id' => $franchiseId, 'vehicle_type_id' => 2, 'status_id' => 1])
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('owner.dashboard')->with('error', 'Access disabled.');
        }

        $stationsQuery = BusStation::where('franchise_id', $franchiseId)
            ->with(['schedules.reservation.dateSchedules', 'toAmounts'])
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
                $allDayIds = $sched->reservation?->dateSchedules->pluck('day_schedule_id')->toArray() ?? [];
                    return [
                        'id' => $sched->id,
                        'bus_station_id' => $sched->bus_station_id,
                        'vehicle_id' => $sched->reservation?->vehicle_id,
                        'day_schedule_ids' => $allDayIds,
                        'reservation_id' => $sched->station_reservation_id,
                        'to_time' => date('H:i', strtotime($sched->to_time)),
                        'from_time' => date('H:i', strtotime($sched->from_time)),
                        'order' => $sched->route_step ?? 0,
                    ];
                })->toArray(),
            ];
        });

        // ... Keep Transactions logic same as your current script ...
        $stationIds = $stationsQuery->pluck('id');
        $filter = $request->query('status', 'completed');
        $transactions = Reservation::with(['fromStation', 'toStation', 'status', 'passengerOwner.user'])
            ->whereIn('from_bus_station_id', $stationIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $statusName = $item->status->name ?? 'Pending';
                $lowerStatus = strtolower($statusName);
                $isCompleted = str_contains($lowerStatus, 'completed');
                $isPaid = str_contains($lowerStatus, 'paid') && !$isCompleted;
                $isPending = !$isPaid && !$isCompleted;
                return [
                    'id' => $item->id,
                    'passenger_name' => $item->passengerOwner?->user?->name ?? 'Guest User',
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

        return Inertia::render('owner/bus-station/Index', [
            'stations' => $stations,
            'franchise_id' => $franchiseId,
            'transactions' => $transactions,
            'initialFilter' => $filter,
            'activeTab' => $request->query('tab', 'stations'),
            'vehicles' => Vehicle::where('franchise_id', $franchiseId)->get(),
            'daySchedules' => DaySchedule::all(),
        ]);
    }

    public function storeBulkSchedule(Request $request)
    {
        $validated = $request->validate([
            'reservation_id' => 'nullable|exists:station_reservations,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'day_schedule_ids' => 'required|array',
            'day_schedule_ids.*' => 'exists:day_schedules,id',
            'stations' => 'required|array',
        ]);

        DB::transaction(function () use ($validated) {
            if (!empty($validated['reservation_id'])) {
                $oldRes = StationReservation::find($validated['reservation_id']);
                if ($oldRes) {
                    $oldRes->schedules()->delete();
                    $oldRes->dateSchedules()->delete();
                    $oldRes->delete();
                }
            }

            // Create the specific instance for THIS journey
            $reservation = StationReservation::create([
                'vehicle_id' => $validated['vehicle_id']
            ]);

            // Insert Operating Days
            foreach ($validated['day_schedule_ids'] as $dayId) {
                DateSchedule::create([
                    'station_reservation_id' => $reservation->id,
                    'day_schedule_id' => $dayId,
                ]);
            }

            // Insert Station Stops
            foreach ($validated['stations'] as $stationId => $data) {
                StationSchedule::create([
                    'station_reservation_id' => $reservation->id,
                    'bus_station_id' => $stationId,
                    'route_step' => $data['order'] ?? 0,
                    'from_time' => $data['from_time'] ?? '00:00',
                    'to_time' => $data['to_time'] ?? '00:00',
                ]);
            }
        });

        return redirect()->back()->with('message', 'Route schedule updated successfully');
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'bus_station_id' => 'required|exists:bus_stations,id',
            'from_time' => 'required',
            'to_time' => 'required',
        ]);

        // Logic for single schedule (Note: may need StationReservation update if you still use this)
        StationSchedule::create($validated);
        return redirect()->back()->with('success', 'Station time added.');
    }


    public function updateSchedule(Request $request, StationSchedule $schedule)
    {
        $validated = $request->validate([
            'from_time' => 'required',
            'to_time' => 'required',
        ]);

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

        $busStation->update([
            'name' => $validated['name'],
            'code_no' => $validated['code_no'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status_id' => $busStation->status_id == 1 ? 1 : 6,
        ]);

        $hasPrevious = StationAmount::where('to_bus_station_id', $busStation->id)->first();
        if ($hasPrevious) {
            $hasPrevious->update(['amount' => $validated['amount']]);
        }

        return redirect()->back()->with('success', 'Station updated.');
    }
}
