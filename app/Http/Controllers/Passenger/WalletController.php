<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\EWallet;
use App\Models\PercentageType;
use App\Models\Revenue;
use App\Models\Route;
use App\Models\Status;
use App\Models\TransactionHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Str;

class WalletController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $wallet = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);
        $transactions = $this->getPaginatedTransactions($wallet->id);

        return Inertia::render('passenger/dashboard/MyWallet', [
            'walletBalance' => (string) $wallet->amount,
            'transactions' => [
                'data' => $transactions->items(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    public function infiniteTransactions(Request $request)
    {
        $user = Auth::user();
        $wallet = EWallet::where('user_id', $user->id)->first();
        if (!$wallet) return response()->json(['data' => [], 'current_page' => 1, 'last_page' => 1]);

        $transactions = $this->getPaginatedTransactions($wallet->id);
        return response()->json([
            'data' => $transactions->items(),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
        ]);
    }

    private function getPaginatedTransactions($walletId)
    {
        return TransactionHistory::where('e_wallet_id', $walletId)
            ->with('status')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($item) {
                $change = $item->new_amount - $item->old_amount;
                $statusName = $item->status->name ?? 'Unknown';
                $displayStatus = in_array($statusName, ['Cancelled', 'Expired', 'Failed']) ? 'Failed' : $statusName;

                return [
                    'id' => $item->id,
                    'amount' => number_format(abs($change), 2),
                    'symbol' => $change >= 0 ? '+' : '-',
                    'balance' => number_format($item->new_amount, 2),
                    'date' => $item->created_at->format('M d, Y'),
                    'time' => $item->created_at->format('h:i A'),
                    'description' => $item->description,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                    'status' => $displayStatus,
                ];
            });
    }

    public function createLoadSession(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = auth()->user();
        $walletRecord = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);
        $lastVerified = $walletRecord->last_otp_verified_at;

        // Security Check: If never verified or verified > 7 days ago
        if (!$lastVerified || Carbon::parse($lastVerified)->addDays(7)->isPast()) {

            // 1. Store load details in session to resume later
            Session::put('pending_wallet_amount', $request->amount);
            Session::put('pending_wallet_lat', $request->latitude);
            Session::put('pending_wallet_lng', $request->longitude);

            // 2. Trigger OTP Send via the OTPController
            $otpController = new OTPController();
            $otpResponse = $otpController->sendOtp($request);

            // 3. Redirect to the verification screen
            return redirect()->route('passenger.otp.index', ['purpose' => 'wallet'])
                           ->with('success', 'Security verification required to proceed with this top-up.');
        }

        // Already verified within 7 days, go straight to PayMongo
        return $this->executePayMongoCheckout($request->amount, $request->latitude, $request->longitude);
    }

    /**
     * Resumes the wallet load process after a successful OTP verification.
     * This is called by the redirect in OTPController@verifyOtp.
     */
    public function resumeAfterOtp(Request $request)
    {
        $amount = Session::get('pending_wallet_amount');
        // Prefer current request location, fallback to session location
        $lat = $request->latitude ?? Session::get('pending_wallet_lat');
        $lng = $request->longitude ?? Session::get('pending_wallet_lng');

        if (!$amount) {
            return redirect()->route('passenger.mywallet')->withErrors(['amount' => 'Top-up session expired. Please try again.']);
        }

        // Clean up session before proceeding
        Session::forget(['pending_wallet_amount', 'pending_wallet_lat', 'pending_wallet_lng']);

        return $this->executePayMongoCheckout($amount, $lat, $lng);
    }

    public function executePayMongoCheckout($amount, $lat = null, $lng = null)
    {
        $user = auth()->user();
        try {
            return DB::transaction(function () use ($amount, $lat, $lng, $user) {
                $wallet = EWallet::where('user_id', $user->id)->lockForUpdate()->first();

                // Check integrity seal
                if (!$wallet->isVerified()) {
                    Log::emergency("TOP-UP BLOCKED: Tampered wallet seal for User ID: " . $user->id);
                    throw new \Exception("Wallet integrity check failed. For your security, this transaction has been blocked. Please contact support.");
                }

                $payload = [
                    'data' => [
                        'attributes' => [
                            'billing' => ['name' => $user->name, 'email' => $user->email],
                            'send_email_receipt' => true,
                            'show_description' => true,
                            'success_url' => route('passenger.wallet.success', ['userId' => $user->id]),
                            'cancel_url'  => route('passenger.mywallet'),
                            'line_items'  => [[
                                'name'     => 'E-Pinoy Wallet Top-up',
                                'amount'   => (int)($amount * 100), // PayMongo uses centavos
                                'currency' => 'PHP',
                                'quantity' => 1,
                            ]],
                            'payment_method_types' => ['card', 'paymaya', 'qrph', 'grab_pay', 'gcash'],
                            'description' => 'Wallet Load for ' . $user->name,
                        ],
                    ],
                ];

                $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                    ->post('https://api.paymongo.com/v1/checkout_sessions', $payload);

                if ($response->successful()) {
                    $sessionData = $response->json()['data'];

                    // Create a pending transaction history entry
                    TransactionHistory::create([
                        'e_wallet_id' => $wallet->id,
                        'status_id'   => Status::where('name', 'Pending')->first()?->id ?? 1,
                        'old_amount'  => $wallet->amount,
                        'new_amount'  => $wallet->amount,
                        'type'        => 'credit',
                        'description' => 'Wallet Top-up (Initiated)',
                        'paymongo_checkout_session_id' => $sessionData['id'],
                        'latitude'    => $lat,
                        'longitude'   => $lng
                    ]);

                    // Use Inertia::location to perform a full window redirect to the PayMongo URL
                    return Inertia::location($sessionData['attributes']['checkout_url']);
                }

                Log::error("PayMongo Session Error: " . $response->body());
                throw new \Exception("Unable to initiate payment session with PayMongo.");
            });
        } catch (\Exception $e) {
            return redirect()->route('passenger.mywallet')->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function loadSuccess(Request $request, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        try {
            DB::beginTransaction();

            $pendingStatusId = Status::where('name', 'Pending')->first()?->id;

            $transaction = TransactionHistory::whereHas('eWallet', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->where('status_id', $pendingStatusId)
                ->where('type', 'credit')
                ->whereNotNull('paymongo_checkout_session_id')
                ->latest()
                ->first();

            if (!$transaction) {
                DB::rollBack();
                return redirect()->route('passenger.mywallet')->with('error', 'No pending payment session found.');
            }

            $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->get("https://api.paymongo.com/v1/checkout_sessions/{$transaction->paymongo_checkout_session_id}");

            if ($response->failed()) throw new \Exception("Could not verify payment status.");

            $attributes = $response->json()['data']['attributes'];
            $isPaid = ($attributes['payment_intent']['attributes']['status'] ?? null) === 'succeeded'
                      || $attributes['status'] === 'completed';

            if ($isPaid) {
                $wallet = EWallet::where('id', $transaction->e_wallet_id)->lockForUpdate()->first();
                $topUpAmount = $attributes['line_items'][0]['amount'] / 100;
                $oldAmount = (float) $wallet->amount;
                $newAmount = $oldAmount + $topUpAmount;

                $wallet->updateAmountAndSeal($newAmount);

                $transaction->update([
                    'status_id'   => Status::where('name', 'Paid')->first()?->id ?? 2,
                    'old_amount'  => $oldAmount,
                    'new_amount'  => $newAmount,
                    'description' => 'Wallet Top-up via PayMongo (Successful)',
                ]);

                DB::commit();

                // Success message is flashed here
                return redirect()->route('passenger.mywallet')
                    ->with('success', '₱' . number_format($topUpAmount, 2) . ' successfully loaded!');
            }

            DB::rollBack();
            return redirect()->route('passenger.mywallet')->with('info', 'Your payment is still being processed.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Top-up Verification Failed: " . $e->getMessage());
            return redirect()->route('passenger.mywallet')->with('error', 'Failed to update wallet balance.');
        }
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

   public function searchBus(Request $request) {
        $request->validate(['unique_name' => 'required|string']);

        // 1. Find the Scanned Destination
        $destinationData = DB::table('station_schedules')
            ->join('bus_stations', 'station_schedules.bus_station_id', '=', 'bus_stations.id')
            ->where('station_schedules.unique_name', $request->unique_name)
            ->select(
                'bus_stations.name',
                'bus_stations.latitude',
                'bus_stations.longitude',
                'station_schedules.*'
            )
            ->first();

        if (!$destinationData) {
            return response()->json(['error' => 'Bus code not found.'], 404);
        }

        $destinationData->address = $this->getReverseGeocode($destinationData->latitude, $destinationData->longitude);

        // 2. Find the Automatic Origin (Route Step 1)
        $originData = DB::table('station_schedules')
            ->join('bus_stations', 'station_schedules.bus_station_id', '=', 'bus_stations.id')
            ->where('station_schedules.station_reservation_id', $destinationData->station_reservation_id)
            ->where('station_schedules.route_step', 1)
            ->select(
                'bus_stations.name',
                'bus_stations.latitude',
                'bus_stations.longitude',
                'station_schedules.*'
            )
            ->first();

        if ($originData) {
            $originData->address = $this->getReverseGeocode($originData->latitude, $originData->longitude);
        }

        // 3. Get the Full Timeline (For the UI)
        $allSchedules = DB::table('station_schedules')
            ->join('bus_stations', 'station_schedules.bus_station_id', '=', 'bus_stations.id')
            ->where('station_reservation_id', $destinationData->station_reservation_id)
            ->orderBy('route_step', 'asc')
            ->select('bus_stations.name', 'bus_stations.latitude', 'bus_stations.longitude', 'station_schedules.*')
            ->get();

        // 4. Calculate Fare using the "While Loop" Jump Logic (Matching Reservation)
        $fareSum = 0;
        if ($originData && $originData->bus_station_id != $destinationData->bus_station_id) {

            $startId = $originData->bus_station_id;
            $endId = $destinationData->bus_station_id;

            $currentId = $startId;
            $step = ($startId < $endId) ? 1 : -1;

            while ($currentId != $endId) {
                $nextId = $currentId + $step;

                $legFare = DB::table('station_amounts')
                    ->where(function ($q) use ($currentId, $nextId) {
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
        }

        // 5. Build the UI Route Stations Array
        $routeStations = $allSchedules->map(fn($s) => [
            'name'         => $s->name,
            'unique_name'  => $s->unique_name,
            'address'      => $this->getReverseGeocode($s->latitude, $s->longitude),
            'arrival'      => $s->from_time ? date('h:i A', strtotime($s->from_time)) : '--:--',
            'departure'    => $s->to_time ? date('h:i A', strtotime($s->to_time)) : '--:--',
            'lat'          => (float)$s->latitude,
            'lng'          => (float)$s->longitude,
            'route_step'   => $s->route_step,
        ]);

        // 6. JSON Response with pure calculation
        return response()->json([
            'origin'         => $originData,
            'destination'    => $destinationData,
            'route_stations' => $routeStations,
            'total_amount'   => (float)$fareSum
        ]);
    }

    public function payBus(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'unique_name' => 'required|string',
            'reservation_id' => 'required|integer',
            'passengers' => 'required|integer|min:1',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = Auth::user();

        try {
            return DB::transaction(function () use ($validated, $user) {
                // 1. Get Wallet and Lock for Update
                $wallet = EWallet::where('user_id', $user->id)->lockForUpdate()->first();

                if (!$wallet || (float)$wallet->amount < (float)$validated['amount']) {
                    throw new \Exception("Insufficient wallet balance. Please top up.");
                }

                // 2. Get the Reservation and Vehicle Details
                $reservation = DB::table('station_reservations')
                    ->join('vehicles', 'station_reservations.vehicle_id', '=', 'vehicles.id')
                    ->where('station_reservations.id', $validated['reservation_id'])
                    ->select(
                        'vehicles.id as vehicle_id',
                        'vehicles.franchise_id',
                        'vehicles.branch_id',
                        'vehicles.vehicle_type_id',
                        'vehicles.driver_id'
                    )
                    ->first();

                if (!$reservation) {
                    throw new \Exception("Reservation or Vehicle details not found.");
                }

                // 3. Fetch IDs for Status and Payment Option
                $paymentOptionId = DB::table('payment_options')->where('name', 'Wallet')->value('id');
                $paidStatusId = Status::where('name', 'Paid')->first()?->id ?? 2;

                // 4. Generate Unique Invoice
                $invoiceNo = "EPINOY-" . strtoupper(Str::random(8));

                // 5. Deduct from Wallet
                $oldAmount = (float) $wallet->amount;
                $newAmount = $oldAmount - (float) $validated['amount'];
                $wallet->updateAmountAndSeal($newAmount);

                // 6. Create Revenue Entry
                $revenueId = DB::table('revenues')->insertGetId([
                    'status_id'           => $paidStatusId,
                    'franchise_id'        => $reservation->franchise_id,
                    'branch_id'           => $reservation->branch_id,
                    'vehicle_type_id'     => $reservation->vehicle_type_id,
                    'driver_id'           => $reservation->driver_id,
                    'passenger_id'        => $user->id,
                    'payment_option_id'   => $paymentOptionId,
                    'invoice_no'          => $invoiceNo,
                    'amount'              => $validated['amount'],
                    'currency'            => 'PHP',
                    'service_type'        => 'Trips',
                    'payment_date'        => now(),
                    'notes'               => "Bus payment for {$validated['passengers']} passenger(s) via {$validated['unique_name']}",
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);

                // 7. Create the Route Entry
                // Fetch origin (Step 1) and destination (the scanned unique_name)
                $dest = DB::table('station_schedules')
                    ->join('bus_stations', 'station_schedules.bus_station_id', '=', 'bus_stations.id')
                    ->where('station_schedules.unique_name', $validated['unique_name'])
                    ->select('bus_stations.name', 'bus_stations.latitude', 'bus_stations.longitude')
                    ->first();

                $orig = DB::table('station_schedules')
                    ->join('bus_stations', 'station_schedules.bus_station_id', '=', 'bus_stations.id')
                    ->where('station_schedules.station_reservation_id', $validated['reservation_id'])
                    ->where('station_schedules.route_step', 1)
                    ->select('bus_stations.name', 'bus_stations.latitude', 'bus_stations.longitude')
                    ->first();

                Route::create([
                    'status_id'            => $paidStatusId,
                    'vehicle_type_id'      => $reservation->vehicle_type_id,
                    'driver_id'            => $reservation->driver_id,
                    'vehicle_id'           => $reservation->vehicle_id,
                    'passenger_id'         => $user->id,
                    'passenger_count'      => $validated['passengers'],
                    'revenue_id'           => $revenueId,
                    'pickup_loc_name'      => $orig->name ?? 'Unknown Origin',
                    'destination_loc_name' => $dest->name ?? 'Unknown Destination',
                    'start_lat'            => $orig->latitude ?? 0,
                    'start_lng'            => $orig->longitude ?? 0,
                    'end_lat'              => $dest->latitude ?? 0,
                    'end_lng'              => $dest->longitude ?? 0,
                    'is_favorite'          => false,
                ]);

                // 8. Calculate Breakdown
                $percentageTypes = PercentageType::all();
                foreach ($percentageTypes as $type) {
                    $earningAmount = ($type->type === 'Percentage')
                        ? ($validated['amount'] * $type->value) / 100
                        : $type->value;

                    DB::table('revenue_breakdowns')->insert([
                        'revenue_id'         => $revenueId,
                        'percentage_type_id' => $type->id,
                        'total_earning'      => $earningAmount,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }

                // 9. Create Transaction History
                TransactionHistory::create([
                    'e_wallet_id' => $wallet->id,
                    'status_id'   => $paidStatusId,
                    'old_amount'  => $oldAmount,
                    'new_amount'  => $newAmount,
                    'type'        => 'debit',
                    'description' => "Bus Trip: {$validated['unique_name']} ({$validated['passengers']} Pax)",
                    'latitude'    => $validated['latitude'] ?? null,
                    'longitude'   => $validated['longitude'] ?? null
                ]);

                return redirect()->route('passenger.transactionhisory');
            });
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }
}
