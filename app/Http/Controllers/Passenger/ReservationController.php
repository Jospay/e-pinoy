<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\BusStation;
use App\Models\Reservation;
use App\Models\StationSchedule;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    private function getStatusIdByWord(string $word)
    {
        return Status::where('name', 'LIKE', "%{$word}%")->first()?->id;
    }

    public function index(Request $request)
    {
        $routes = \App\Models\StationReservation::with([
            'vehicle',
            'dateSchedules.daySchedule',
            'schedules.busStation'
        ])->get()->map(function($reservation) {
            $stops = $reservation->schedules->sortBy('route_step')->values();
            $originStation = $stops->first()?->busStation;
            $destinationStation = $stops->last()?->busStation;

            return [
                'id' => $reservation->id,
                'vehicle_info' => [
                    'name' => $reservation->vehicle->model,
                    'plate' => $reservation->vehicle->plate_number,
                ],
                'days' => $reservation->dateSchedules->pluck('daySchedule.name'),
                'origin' => [
                    'id' => $originStation?->id,
                    'name' => $originStation?->name ?? 'N/A',
                    'lat' => (float)$originStation?->latitude,
                    'lng' => (float)$originStation?->longitude,
                    'address' => $this->getReverseGeocode($originStation?->latitude, $originStation?->longitude),
                ],
                'destination_name' => $destinationStation?->name ?? 'N/A',
                'start_time' => $stops->first() ? date('h:i A', strtotime($stops->first()->from_time)) : 'N/A',
                'stops' => $stops->map(fn($s) => [
                    'station_name' => $s->busStation->name,
                    'arrival' => $s->to_time ? date('h:i A', strtotime($s->to_time)) : '--:--',
                    'departure' => $s->from_time ? date('h:i A', strtotime($s->from_time)) : '--:--',
                    'order' => $s->route_step,
                    'station_id' => $s->bus_station_id,
                    'address' => $this->getReverseGeocode($s->busStation->latitude, $s->busStation->longitude),
                ])
            ];
        });

        return Inertia::render('passenger/dashboard/Index', [
            'availableRoutes' => $routes
        ]);
    }

    private function getReverseGeocode($lat, $lng)
    {
        if (!$lat || !$lng) return "Location unavailable";
        $latMod = round($lat, 4);
        $lngMod = round($lng, 4);
        $cacheKey = "addr_v6_{$latMod}_{$lngMod}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($lat, $lng) {
            try {
                usleep(500000);
                $response = Http::withHeaders(['User-Agent' => 'BusTerminal_System'])
                    ->timeout(3)->get("https://nominatim.openstreetmap.org/reverse", [
                        'lat' => $lat,
                        'lon' => $lng,
                        'format' => 'json',
                    ]);
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['display_name'] ?? "Terminal Location ($lat, $lng)";
                }
                return "Terminal at $lat, $lng";
            } catch (\Exception $e) {
                return "Address unavailable";
            }
        });
    }

    public function create(Request $request)
    {
        $stationReservationId = $request->query('station_reservation_id');
        $fromStationId = $request->query('from_id');

        $trip = \App\Models\StationReservation::with([
            'vehicle',
            'schedules.busStation.toAmounts',
            'dateSchedules.daySchedule'
        ])->findOrFail($stationReservationId);

        // 1. Get all schedules for this trip, sorted by their sequence
        $allSchedules = $trip->schedules->sortBy('route_step')->values();

        // 2. Identify the Origin
        $originSchedule = $allSchedules->where('bus_station_id', $fromStationId)->first();

        if (!$originSchedule) {
            return redirect()->back()->with('error', 'Origin station not found.');
        }


        $originIndex = $allSchedules->search(fn($s) => $s->id === $originSchedule->id);

        // 4. Destinations are only stations that appear AFTER the origin in the sorted sequence
        $availableDestinations = $allSchedules->slice($originIndex + 1)
    ->map(function($s) use ($allSchedules, $originIndex) {

        // Identify the path of stations between the user's start and this specific destination
        $path = $allSchedules->slice($originIndex + 1, $allSchedules->search(fn($item) => $item->id === $s->id) - $originIndex);

        $fareSum = $path->map(function($step, $key) use ($allSchedules, $originIndex, $path) {
            // Find the station immediately before this one in the CURRENT journey
            $currentIndexInAll = $allSchedules->search(fn($item) => $item->id === $step->id);
            $previousStationInJourney = $allSchedules[$currentIndexInAll - 1]->busStation;
            $currentStationInJourney = $step->busStation;

            // LOOKUP FARE: Check both directions in the database (A->B or B->A)
            return \App\Models\StationAmount::where(function($q) use ($previousStationInJourney, $currentStationInJourney) {
                    $q->where('from_bus_station_id', $previousStationInJourney->id)
                      ->where('to_bus_station_id', $currentStationInJourney->id);
                })
                ->orWhere(function($q) use ($previousStationInJourney, $currentStationInJourney) {
                    $q->where('from_bus_station_id', $currentStationInJourney->id)
                      ->where('to_bus_station_id', $previousStationInJourney->id);
                })
                ->first()?->amount ?? 0;
        })->sum();

        return [
            'id' => $s->busStation->id,
            'name' => $s->busStation->name,
            'calculated_fare' => (float)$fareSum,
        ];
    })->values();

        // 5. Timeline for Sidebar (Always matches the physical path of the bus)
        $fullTimeline = $allSchedules->map(fn($s) => [
            'name' => $s->busStation->name,
            'arrival' => $s->to_time ? date('h:i A', strtotime($s->to_time)) : '--:--',
            'departure' => $s->from_time ? date('h:i A', strtotime($s->from_time)) : '--:--',
            'address' => $this->getReverseGeocode($s->busStation->latitude, $s->busStation->longitude),
        ])->values();

        return Inertia::render('passenger/dashboard/Reserve', [
            'origin' => [
                'id' => $originSchedule->busStation->id,
                'name' => $originSchedule->busStation->name,
                'lat' => (float)$originSchedule->busStation->latitude,
                'lng' => (float)$originSchedule->busStation->longitude,
                'departure_time' => date('h:i A', strtotime($originSchedule->from_time)),
                'schedule_id' => $originSchedule->id
            ],
            'destinations' => $availableDestinations,
            'route_stations' => $fullTimeline,
            'available_days' => $trip->dateSchedules->map(fn($ds) => $ds->daySchedule->name)->values(),
            'vehicle_info' => [
                'id' => $trip->vehicle->id,
                'name' => $trip->vehicle->model,
                'plate' => $trip->vehicle->plate_number,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id'          => 'required|exists:vehicles,id',
            'from_bus_station_id' => 'required|exists:bus_stations,id',
            'to_bus_station_id'   => 'required|exists:bus_stations,id',
            'station_schedule_id' => 'required|exists:station_schedules,id',
            'passenger_count'     => 'required|integer|min:1|max:20',
            'reserve_date'        => 'required|date|after_or_equal:today',
            'amount'              => 'required|numeric',
        ]);

        $user = auth()->user();
        $sched = StationSchedule::findOrFail($validated['station_schedule_id']);
        $origin = BusStation::findOrFail($validated['from_bus_station_id']);
        $destination = BusStation::findOrFail($validated['to_bus_station_id']);

        $pendingId = $this->getStatusIdByWord('Pending') ?? 6;

        try {
            DB::beginTransaction();
            $qrName = 'QR-' . strtoupper(Str::random(12));
            $reservation = Reservation::create([
                'vehicle_id'          => $validated['vehicle_id'],
                'passenger_id'        => $user->id,
                'from_bus_station_id' => $validated['from_bus_station_id'],
                'to_bus_station_id'   => $validated['to_bus_station_id'],
                'status_id'           => $pendingId,
                'passenger_count'     => $validated['passenger_count'],
                'amount'              => $validated['amount'],
                'reserve_from_time'   => $sched->from_time,
                'reserve_to_time'     => $sched->to_time,
                'reserve_date'        => $validated['reserve_date'],
                'qrcode_name'         => $qrName,
                'paymongo_checkout_session_id' => 'INITIALIZING',
            ]);

            $routeName = "{$origin->name} to {$destination->name}";
            $paymongoSession = $this->createPaymongoCheckoutSession($user, $validated['amount'], $routeName, $reservation);

            $reservation->update(['paymongo_checkout_session_id' => $paymongoSession['id']]);

            DB::commit();
            return Inertia::location($paymongoSession['attributes']['checkout_url']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Reservation Store Error: " . $e->getMessage());
            return back()->withErrors(['amount' => 'Payment system error: ' . $e->getMessage()]);
        }
    }

    public function success(Request $request, $qrcode_name)
    {
        try {
            DB::beginTransaction();
            $reservation = Reservation::where('qrcode_name', $qrcode_name)->lockForUpdate()->firstOrFail();
            $paidStatusId = $this->getStatusIdByWord('Paid') ?? 1;

            if ($reservation->status_id == $paidStatusId) {
                DB::commit();
                return $this->renderSuccess($reservation);
            }

            $sessionId = $reservation->paymongo_checkout_session_id;
            $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->get("https://api.paymongo.com/v1/checkout_sessions/{$sessionId}");

            if ($response->failed()) throw new \Exception('Failed to fetch PayMongo session.');

            $data = $response->json()['data']['attributes'];
            if (($data['status'] ?? 'open') === 'completed') {
                $reservation->update(['status_id' => $paidStatusId]);
                DB::commit();
                return $this->renderSuccess($reservation->refresh());
            }

            DB::rollBack();
            return redirect()->route('passenger.dashboard')->with('error', 'Payment not confirmed.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Payment Verification Error: " . $e->getMessage());
            return redirect()->route('passenger.dashboard')->with('error', 'Verification failed.');
        }
    }

    private function renderSuccess($reservation)
    {
        return Inertia::render('passenger/dashboard/Success', [
            'reservation' => $reservation->load(['fromStation', 'toStation', 'passenger', 'status', 'vehicle'])
        ]);
    }

    protected function createPaymongoCheckoutSession($user, $amount, $routeName, Reservation $reservation)
    {
        $formattedAmount = (int)($amount * 100);
        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => ['name' => $user->name, 'email' => trim($user->email)],
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'cancel_url' => route('passenger.dashboard'),
                    'success_url' => route('passenger.reservation.success', ['reservation' => $reservation->qrcode_name]),
                    'line_items' => [[
                        'name' => 'Bus Ticket: ' . $routeName,
                        'amount' => $formattedAmount,
                        'currency' => 'PHP',
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => ['card', 'paymaya', 'qrph', 'billease', 'grab_pay', 'dob'],
                    'description' => 'Booking ID: ' . $reservation->qrcode_name,
                ],
            ],
        ];

        $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
            ->post('https://api.paymongo.com/v1/checkout_sessions', $payload);

        if ($response->failed()) throw new \Exception('PayMongo Session Error: ' . ($response->json()['errors'][0]['detail'] ?? 'Unknown Error'));

        return $response->json()['data'];
    }
}
