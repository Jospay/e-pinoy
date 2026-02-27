<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\BusStation;
use App\Models\Reservation;
use App\Models\StationSchedule;
use App\Models\StationAmount;
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
        $validFranchiseIds = BusStation::where('status_id', 1)
            ->select('franchise_id', DB::raw('count(*) as total'))
            ->groupBy('franchise_id')
            ->having('total', '>=', 2)
            ->pluck('franchise_id');

        $stations = BusStation::whereIn('franchise_id', $validFranchiseIds)
            ->where('status_id', 1)
            ->whereHas('schedules')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code_no,
                    'lat' => (float)$s->latitude,
                    'lng' => (float)$s->longitude,
                    'address' => $this->getReverseGeocode($s->latitude, $s->longitude),
                ];
            });

        return Inertia::render('passenger/dashboard/Index', [
            'stations' => $stations
        ]);
    }

    private function getReverseGeocode($lat, $lng)
    {
        $latMod = round($lat, 4);
        $lngMod = round($lng, 4);
        $cacheKey = "addr_v5_{$latMod}_{$lngMod}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($lat, $lng) {
            try {
                usleep(1000000);
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
        $fromId = $request->query('from_id');
        $origin = BusStation::with('schedules')->findOrFail($fromId);

        $destinations = BusStation::where('franchise_id', $origin->franchise_id)
            ->where('id', '!=', $fromId)
            ->where('status_id', 1)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function($dest) use ($fromId) {
                $totalAmount = StationAmount::where('to_bus_station_id', '>', min($fromId, $dest->id))
                    ->where('to_bus_station_id', '<=', max($fromId, $dest->id))
                    ->whereHas('toStation', function($q) use ($dest) {
                        $q->where('franchise_id', $dest->franchise_id);
                    })
                    ->sum('amount');

                return [
                    'id' => $dest->id,
                    'name' => $dest->name,
                    'code' => $dest->code_no,
                    'calculated_fare' => (float)($totalAmount > 0 ? $totalAmount : 15.0),
                ];
            });

        return Inertia::render('passenger/dashboard/Reserve', [
            'origin' => [
                'id' => $origin->id,
                'name' => $origin->name,
                'code' => $origin->code_no,
                'lat' => (float)$origin->latitude,
                'lng' => (float)$origin->longitude,
                'schedules' => $origin->schedules->map(fn($sched) => [
                    'id' => $sched->id,
                    'from_time' => $sched->from_time,
                    'to_time' => $sched->to_time,
                    'time_range' => date('h:i A', strtotime($sched->from_time)) . ' - ' . date('h:i A', strtotime($sched->to_time))
                ]),
            ],
            'destinations' => $destinations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_bus_station_id' => 'required|exists:bus_stations,id',
            'to_bus_station_id'   => 'required|exists:bus_stations,id',
            'station_schedule_id' => 'required|exists:station_schedules,id',
            'reserve_date'         => 'required|date|after_or_equal:today',
            'amount'               => 'required|numeric',
        ]);

        $user = auth()->user();
        $sched = StationSchedule::findOrFail($validated['station_schedule_id']);
        $origin = BusStation::findOrFail($validated['from_bus_station_id']);
        $destination = BusStation::findOrFail($validated['to_bus_station_id']);

        $pendingId = $this->getStatusIdByWord('Pending') ?? 6;

        try {
            $qrName = 'QR-' . strtoupper(Str::random(12));

            $reservation = Reservation::create([
                'passenger_id'        => $user->id,
                'from_bus_station_id' => $validated['from_bus_station_id'],
                'to_bus_station_id'   => $validated['to_bus_station_id'],
                'status_id'           => $pendingId,
                'amount'              => $validated['amount'],
                'reserve_from_time'   => $sched->from_time,
                'reserve_to_time'     => $sched->to_time,
                'reserve_date'        => $validated['reserve_date'],
                'qrcode_name'         => $qrName,
                'paymongo_checkout_session_id' => 'INITIALIZING',
            ]);

            $routeName = "{$origin->name} to {$destination->name}";
            $paymongoSession = $this->createPaymongoCheckoutSession($user, $validated['amount'], $routeName, $reservation);

            $reservation->update([
                'paymongo_checkout_session_id' => $paymongoSession['id']
            ]);

            return Inertia::location($paymongoSession['attributes']['checkout_url']);

        } catch (\Exception $e) {
            Log::error("Reservation Store Error: " . $e->getMessage());
            return back()->withErrors(['amount' => 'Payment system error: ' . $e->getMessage()]);
        }
    }

    public function success(Request $request, Reservation $reservation)
    {
        try {
            DB::beginTransaction();

            // Row-level lock to prevent duplicate processing
            $reservation = Reservation::where('id', $reservation->id)->lockForUpdate()->first();

            $paidStatusId = $this->getStatusIdByWord('Paid') ?? 1;

            // 1. Check if already marked as paid
            if ($reservation->status_id == $paidStatusId) {
                DB::commit();
                return $this->renderSuccess($reservation);
            }

            $sessionId = $reservation->paymongo_checkout_session_id;

            // 2. Fetch session from PayMongo
            $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->get("https://api.paymongo.com/v1/checkout_sessions/{$sessionId}");

            if ($response->failed()) {
                throw new \Exception('Failed to fetch PayMongo session.');
            }

            $data = $response->json()['data']['attributes'];
            $paymongoStatus = $data['status'] ?? 'open';
            $payments = $data['payments'] ?? [];

            // 3. Logic to determine if payment was successful
            $isPaid = ($paymongoStatus === 'completed');

            // If session is "open" but the payments array contains a "paid" entry, it is successful
            if (!$isPaid && !empty($payments)) {
                foreach ($payments as $payment) {
                    if (($payment['attributes']['status'] ?? '') === 'paid') {
                        $isPaid = true;
                        break;
                    }
                }
            }

            if ($isPaid) {
                $reservation->update(['status_id' => $paidStatusId]);
                DB::commit();
                return $this->renderSuccess($reservation->refresh());
            }

            // 4. If not paid, rollback and redirect
            DB::rollBack();
            Log::warning("Payment Verification Failed for Res #{$reservation->id}. Status: {$paymongoStatus}");
            return redirect()->route('passenger.dashboard')->with('error', 'Payment not confirmed yet. Please check your email for the receipt.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Payment Verification Error: " . $e->getMessage());
            return redirect()->route('passenger.dashboard')->with('error', 'An error occurred during verification.');
        }
    }

    private function renderSuccess($reservation)
    {
        return Inertia::render('passenger/dashboard/Success', [
            'reservation' => $reservation->load(['fromStation', 'toStation', 'passenger', 'status'])
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

        if ($response->failed()) {
            throw new \Exception('PayMongo Session Error: ' . ($response->json()['errors'][0]['detail'] ?? 'Unknown Error'));
        }

        return $response->json()['data'];
    }
}
