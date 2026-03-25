<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\BusStation;
use App\Models\Reservation;
use App\Models\StationAmount;
use App\Models\StationReservation;
use App\Models\StationSchedule;
use App\Models\Status;
use App\Models\Vehicle;
use App\Models\EWallet;
use App\Models\TransactionHistory;
use Carbon\Carbon;
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

    /**
     * Real-time seat availability
     */
    public function getAvailability(Request $request)
    {
        $request->validate([
            'vehicle_id'   => 'required|exists:vehicles,id',
            'reserve_date' => 'required|date',
        ]);

        $paidStatusId = $this->getStatusIdByWord('Paid');

        $bookedSeats = Reservation::where('vehicle_id', $request->vehicle_id)
            ->whereDate('reserve_date', $request->reserve_date)
            ->whereIn('status_id', array_filter([$paidStatusId]))
            ->sum('passenger_count');

        return response()->json([
            'booked' => (int)$bookedSeats,
        ]);
    }

    public function index(Request $request)
    {
        $routes = StationReservation::with([
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
                'end_time' => $stops->first() ? date('h:i A', strtotime($stops->first()->to_time)) : 'N/A',
                'stops' => $stops->map(fn($s) => [
                    'station_name' => $s->busStation->name,
                    'arrival' => $s->from_time ? date('h:i A', strtotime($s->from_time)) : '--:--',
                    'departure' => $s->to_time ? date('h:i A', strtotime($s->to_time)) : '--:--',
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

        $trip = StationReservation::with([
            'vehicle',
            'schedules.busStation.toAmounts',
            'dateSchedules.daySchedule'
        ])->findOrFail($stationReservationId);

        $allSchedules = $trip->schedules->sortBy('route_step')->values();
        $originSchedule = $allSchedules->where('bus_station_id', $fromStationId)->first();

        if (!$originSchedule) {
            return redirect()->back()->with('error', 'Origin station not found.');
        }

        $originIndex = $allSchedules->search(fn($s) => $s->id === $originSchedule->id);

        $availableDestinations = $allSchedules->slice($originIndex + 1)
        ->map(function ($targetSchedule) use ($allSchedules, $originIndex) {
            $fareSum = 0;

            $startId = $allSchedules[$originIndex]->bus_station_id;
            $endId = $targetSchedule->bus_station_id;

            $currentId = $startId;
            $step = ($startId < $endId) ? 1 : -1;

            while ($currentId != $endId) {
                $nextId = $currentId + $step;

                $legFare = StationAmount::where(function ($q) use ($currentId, $nextId) {
                        $q->where('from_bus_station_id', $currentId)
                          ->where('to_bus_station_id', $nextId);
                    })
                    ->orWhere(function ($q) use ($currentId, $nextId) {
                        $q->where('from_bus_station_id', $nextId)
                          ->where('to_bus_station_id', $currentId);
                    })
                    ->value('amount');

                $fareSum += $legFare ?? 0;
                $currentId = $nextId;
            }

            return [
                'id' => $targetSchedule->busStation->id,
                'name' => $targetSchedule->busStation->name,
                'calculated_fare' => (float)$fareSum,
            ];
        })->values();

        $fullTimeline = $allSchedules->map(fn($s) => [
            'name' => $s->busStation->name,
            'arrival' => $s->to_time ? date('h:i A', strtotime($s->from_time)) : '--:--',
            'departure' => $s->from_time ? date('h:i A', strtotime($s->to_time)) : '--:--',
            'address' => $this->getReverseGeocode($s->busStation->latitude, $s->busStation->longitude),
        ])->values();

        $walletBalance = auth()->user()->eWallet?->amount ?? 0;

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
                'capacity' => $trip->vehicle->capacity,
            ],
            'walletBalance' => $walletBalance,
            'station_reservation_id' => $stationReservationId
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'station_reservation_id' => 'required|exists:station_reservations,id',
            'vehicle_id'             => 'required|exists:vehicles,id',
            'from_bus_station_id'    => 'required|exists:bus_stations,id',
            'to_bus_station_id'      => 'required|exists:bus_stations,id',
            'station_schedule_id'    => 'required|exists:station_schedules,id',
            'passenger_count'        => 'required|integer|min:1',
            'reserve_date'           => 'required|date|after_or_equal:today',
            'amount'                 => 'required|numeric',
            'payment_method'         => 'required|string|in:Wallet,Online Payment',
            'latitude'               => 'nullable|numeric',
            'longitude'              => 'nullable|numeric',
        ]);

        $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
        $trip = StationReservation::with('dateSchedules.daySchedule')
            ->findOrFail($validated['station_reservation_id']);

        // Day Validation
        $reserveDay = Carbon::parse($validated['reserve_date'])->format('l');
        $allowedDays = $trip->dateSchedules->map(fn($ds) => strtolower($ds->daySchedule->name));
        if (!$allowedDays->contains(strtolower($reserveDay))) {
            return back()->withErrors([
                'reserve_date' => "This vehicle does not operate on {$reserveDay}s."
            ]);
        }

        $paidStatusId = $this->getStatusIdByWord('Paid');
        $pendingStatusId = $this->getStatusIdByWord('Pending');

        // Seat Availability Check
        $bookedSeats = Reservation::where('vehicle_id', $validated['vehicle_id'])
            ->whereDate('reserve_date', $validated['reserve_date'])
            ->whereIn('status_id', array_filter([$paidStatusId]))
            ->sum('passenger_count');

        $availableSeats = $vehicle->capacity - $bookedSeats;
        if ($validated['passenger_count'] > $availableSeats) {
            return back()->withErrors([
                'passenger_count' => "Sorry, only $availableSeats seats left for this date."
            ]);
        }

        $user = auth()->user();
        $sched = StationSchedule::findOrFail($validated['station_schedule_id']);
        $origin = BusStation::findOrFail($validated['from_bus_station_id']);
        $destination = BusStation::findOrFail($validated['to_bus_station_id']);

        try {
            DB::beginTransaction();
            $qrName = 'QR-' . strtoupper(\Str::random(12));

            if ($validated['payment_method'] === 'Wallet') {
                $walletRecord = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);

                // OTP Security Check (7-day window)
                $lastVerified = $walletRecord->last_otp_verified_at;
                if (!$lastVerified || Carbon::parse($lastVerified)->addDays(7)->isPast()) {
                    session(['pending_reservation' => $validated]);
                    session()->save();

                    $otpController = new OTPController();
                    $otpController->sendOtp($request);

                    DB::rollBack();
                    return redirect()->route('passenger.otp.index', ['purpose' => 'reservation'])
                        ->with('requires_otp', true);
                }

                $wallet = EWallet::where('id', $walletRecord->id)->lockForUpdate()->first();

                if (!$wallet->isVerified()) {
                    Log::emergency("SECURITY ALERT: Tampered wallet detected for User ID: " . $user->id);
                    throw new \Exception("Wallet integrity check failed. Please contact support.");
                }

                if ($wallet->amount < $validated['amount']) {
                    throw new \Exception("Insufficient wallet balance. Your balance: ₱" . number_format($wallet->amount, 2));
                }

                $oldAmount = $wallet->amount;
                $newAmount = $oldAmount - $validated['amount'];
                $wallet->updateAmountAndSeal($newAmount);

                $reservation = Reservation::create([
                    'vehicle_id' => $validated['vehicle_id'],
                    'passenger_id' => $user->id,
                    'from_bus_station_id' => $validated['from_bus_station_id'],
                    'to_bus_station_id' => $validated['to_bus_station_id'],
                    'status_id' => $paidStatusId,
                    'passenger_count' => $validated['passenger_count'],
                    'amount' => $validated['amount'],
                    'reserve_from_time' => $sched->from_time,
                    'reserve_to_time' => $sched->to_time,
                    'reserve_date' => $validated['reserve_date'],
                    'qrcode_name' => $qrName,
                    'payment_options' => 'Wallet',
                ]);

                TransactionHistory::create([
                    'e_wallet_id' => $wallet->id,
                    'status_id' => $paidStatusId,
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'type'       => 'debit',
                    'description' => 'Bus Reservation Payment: ' . $qrName ,
                    'latitude'    => $validated['latitude'],
                    'longitude'   => $validated['longitude']
                ]);

                DB::commit();

                return Inertia::render('passenger/dashboard/Success', [
                    'reservation' => $reservation->load(['fromStation', 'toStation', 'passenger', 'status', 'vehicle'])
                ]);
            }

            // --- Online Payment Flow ---
            $reservation = Reservation::create([
                'vehicle_id' => $validated['vehicle_id'],
                'passenger_id' => $user->id,
                'from_bus_station_id' => $validated['from_bus_station_id'],
                'to_bus_station_id' => $validated['to_bus_station_id'],
                'status_id' => $pendingStatusId,
                'passenger_count' => $validated['passenger_count'],
                'amount' => $validated['amount'],
                'reserve_from_time' => $sched->from_time,
                'reserve_to_time' => $sched->to_time,
                'reserve_date' => $validated['reserve_date'],
                'qrcode_name' => $qrName,
                'payment_options' => 'Online Payment',
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
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function success(Request $request, Reservation $reservation)
{
    try {
        $reservation->lockForUpdate();

        // Use your helper to find the real 'Paid' ID
        $paidStatusId = $this->getStatusIdByWord('Paid');

        if (!$paidStatusId) {
            Log::emergency("Critical Error: 'Paid' status not found in the statuses table.");
            return redirect()->route('passenger.dashboard')->with('error', 'System configuration error.');
        }

        // 1. Check if already marked as Paid (Wallet or completed PayMongo)
        if ((int)$reservation->status_id === (int)$paidStatusId) {
            return $this->renderSuccess($reservation->load(['fromStation', 'toStation', 'passenger', 'status', 'vehicle']));
        }

        // 2. Online Payment Flow
        if (!$reservation->paymongo_checkout_session_id) {
            return redirect()->route('passenger.dashboard')
                ->with('error', 'Payment session not found.');
        }

        $sessionId = $reservation->paymongo_checkout_session_id;
        $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
            ->get("https://api.paymongo.com/v1/checkout_sessions/{$sessionId}");

        if ($response->failed()) {
            throw new \Exception('Failed to fetch PayMongo session.');
        }

        $attributes = $response->json()['data']['attributes'] ?? [];
        $paymongoStatus = $attributes['status'] ?? 'open';
        $payments = $attributes['payments'] ?? [];

        $isPaid = ($paymongoStatus === 'completed');
        if (!$isPaid && !empty($payments)) {
            foreach ($payments as $payment) {
                if (($payment['attributes']['status'] ?? '') === 'paid') {
                    $isPaid = true;
                    break;
                }
            }
        }

        if ($isPaid) {
            $reservation->status_id = $paidStatusId;
            $reservation->save();

            return $this->renderSuccess($reservation->load(['fromStation', 'toStation', 'passenger', 'status', 'vehicle']));
        }

        return redirect()->route('passenger.dashboard')
            ->with('error', 'Payment not completed. Please try again.');

    } catch (\Exception $e) {
        Log::error("Payment Verification Error: " . $e->getMessage());
        return redirect()->route('passenger.dashboard')->with('error', 'Verification failed.');
    }
}

    private function renderSuccess($reservation)
    {
        $reservation->reserve_date = Carbon::parse($reservation->reserve_date)->format('M d, Y');

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
