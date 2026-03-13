<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\TaxiReservation;
use App\Models\EWallet;
use App\Models\TransactionHistory;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TaxiReservationController extends Controller
{
    private function getStatusIdByWord(string $word)
    {
        return Status::where('name', 'LIKE', "%{$word}%")->first()?->id;
    }

    public function index(Request $request, $id) // Remove 'Reservation' type-hint
    {
        // Manually find the Bus Reservation
        $busReservation = Reservation::with(['fromStation', 'toStation'])->find($id);
        $bookingType = $request->query('type', 'after');

        return Inertia::render('passenger/dashboard/TaxiReservation', [
            'busReservation' => $busReservation,
            'bookingType'    => $bookingType,
            'defaultPickup'  => $bookingType === 'after' ? $busReservation->toStation?->name : '',
            'defaultDest'    => $bookingType === 'before' ? $busReservation->fromStation?->name : '',
            'passengerCount' => $busReservation->passenger_count,
            'walletBalance'  => (float) (auth()->user()->eWallet?->amount ?? 0),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reservation_id'       => 'required|exists:reservations,id',
            'booking_type'         => 'required|in:before,after',
            'time_pickup'          => 'nullable|required_if:booking_type,before',
            'passenger_count'      => 'required|integer|min:1|max:4',
            'amount'               => 'required|numeric|min:50',
            'pickup_loc_name'      => 'required|string',
            'destination_loc_name' => 'required|string',
            'start_lat'            => 'required|numeric',
            'start_lng'            => 'required|numeric',
            'end_lat'              => 'required|numeric',
            'end_lng'              => 'required|numeric',
            'distance_km'          => 'required|numeric',
            'payment_options'      => 'required|string|in:Wallet,Online Payment',
            'latitude'             => 'nullable|numeric',
            'longitude'            => 'nullable|numeric',
        ]);

        $user = auth()->user();
        $paidStatusId = $this->getStatusIdByWord('Paid');
        $pendingStatusId = $this->getStatusIdByWord('Pending');

        try {
            DB::beginTransaction();

            $busReservation = Reservation::findOrFail($validated['reservation_id']);
            $refNumber = 'TXI-' . strtoupper(Str::random(10));

            $taxiData = array_merge($validated, [
                'passenger_id' => $user->id,
                'vehicle_id'   => 1,
                'reserve_date' => Carbon::parse($busReservation->reserve_date)->format('Y-m-d'),
                'qrcode_name'  => $refNumber
            ]);

            if ($validated['payment_options'] === 'Wallet') {
                $walletCheck = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);

                // OTP Security Check (7-day window)
                $lastVerified = $walletCheck->last_otp_verified_at;
                if (!$lastVerified || Carbon::parse($lastVerified)->addDays(7)->isPast()) {
                    // We add 'reservation_type' => 'taxi' so the OTP controller knows which flow to use
                    session(['pending_reservation' => array_merge($validated, ['reservation_type' => 'taxi'])]);

                    $otpController = new OTPController();
                    $otpController->sendOtp($request);

                    DB::rollBack();
                    return redirect()->route('passenger.otp.index')->with('requires_otp', true);
                }

                $wallet = EWallet::where('id', $walletCheck->id)->lockForUpdate()->first();

                // 1. Initial Security Check
                if (!$wallet->isVerified()) {
                    Log::emergency("SECURITY ALERT: Tampered wallet detected for User ID: " . $user->id);
                    throw new \Exception("Wallet integrity check failed. For your security, this transaction has been blocked. Please contact support.");
                }

                // 2. Balance Check
                if ($wallet->amount < $validated['amount']) {
                    throw new \Exception('Insufficient wallet balance.');
                }

                $oldAmount = $wallet->amount;
                $newAmount = $oldAmount - $validated['amount'];

                // 3. Update Amount and Seal (Unified Method)
                $wallet->updateAmountAndSeal($newAmount);

                // 4. Create the Taxi Reservation
                $taxi = TaxiReservation::create(array_merge($taxiData, [
                    'status_id' => $paidStatusId,
                ]));

                // 5. Log Transaction History
                TransactionHistory::create([
                    'e_wallet_id' => $wallet->id,
                    'old_amount'  => $oldAmount,
                    'new_amount'  => $newAmount,
                    'type'        => 'debit',
                    'description' => 'Taxi Booking Payment: ' . $taxi->qrcode_name,
                    'latitude'    => $validated['latitude'],
                    'longitude'   => $validated['longitude']
                ]);

                DB::commit();
                return redirect()->route('passenger.reservationtaxi.success', $taxi->id);
            }

            // Online Payment Flow
            $taxi = TaxiReservation::create(array_merge($taxiData, [
                'status_id' => $pendingStatusId,
                'paymongo_checkout_session_id' => 'INITIALIZING',
            ]));

            $paymongoSession = $this->createPaymongoTaxiSession($user, $validated['amount'], $taxi);
            $taxi->update(['paymongo_checkout_session_id' => $paymongoSession['id']]);

            DB::commit();
            return Inertia::location($paymongoSession['attributes']['checkout_url']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Taxi Store Error: " . $e->getMessage());
            // Returning the specific error message so the user knows if it's a balance or security issue
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    /**
     * Updated Success Method with strict PayMongo verification
     */
    public function success(TaxiReservation $reservation)
    {
        try {
            // Use database locking to prevent race conditions during update
            DB::beginTransaction();

            // Refresh and lock the record
            $reservation = TaxiReservation::where('id', $reservation->id)->lockForUpdate()->first();
            $paidStatusId = $this->getStatusIdByWord('Paid');

            // 1. Check if it's already marked as paid (Wallet or already verified)
            if ((int)$reservation->status_id === (int)$paidStatusId) {
                DB::commit();
                return $this->renderTaxiSuccess($reservation);
            }

            // 2. If it's an online payment, verify with PayMongo
            $sessionId = $reservation->paymongo_checkout_session_id;
            if ($sessionId && $sessionId !== 'INITIALIZING') {

                $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                    ->get("https://api.paymongo.com/v1/checkout_sessions/{$sessionId}");

                if ($response->successful()) {
                    $data = $response->json()['data'];
                    $attributes = $data['attributes'] ?? [];
                    $sessionStatus = $attributes['status'] ?? 'open';
                    $payments = $attributes['payments'] ?? [];

                    // Logic matches your Bus Reservation Controller: check session OR individual payments
                    $isPaid = ($sessionStatus === 'completed');

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
                        return $this->renderTaxiSuccess($reservation->fresh());
                    }
                }
            }

            DB::rollBack();
            // 3. If we get here, payment wasn't confirmed
            return redirect()->route('passenger.dashboard')
                ->with('error', 'Payment verification failed or is still processing.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Taxi Verification Error: " . $e->getMessage());
            return redirect()->route('passenger.dashboard')->with('error', 'Error verifying payment.');
        }
    }

    protected function createPaymongoTaxiSession($user, $amount, $taxi)
    {
        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => ['name' => $user->name, 'email' => trim($user->email)],
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'success_url' => route('passenger.reservationtaxi.success', ['reservation' => $taxi->id]),
                    'cancel_url'  => route('passenger.dashboard'),
                    'line_items'  => [[
                        'name'     => 'Taxi Service: ' . $taxi->pickup_loc_name,
                        'amount'   => (int)($amount * 100),
                        'currency' => 'PHP',
                        'quantity' => 1,
                    ]],
                    'payment_method_types' => ['card', 'paymaya', 'qrph', 'billease', 'grab_pay', 'dob'],
                    'description' => 'Taxi Booking ID: ' . $taxi->qrcode_name,
                ],
            ],
        ];

        $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
            ->post('https://api.paymongo.com/v1/checkout_sessions', $payload);

        if ($response->failed()) {
            throw new \Exception('PayMongo Session Error: ' . ($response->json()['errors'][0]['detail'] ?? 'Unknown'));
        }

        return $response->json()['data'];
    }

    private function renderTaxiSuccess($reservation)
    {
        $reservation->reserve_date = Carbon::parse($reservation->reserve_date)->format('M d, Y');

        return Inertia::render('passenger/dashboard/TaxiSuccess', [
            'reservation' => $reservation->load(['status', 'vehicle', 'reservation']),
            'bookingType' => $reservation->booking_type
        ]);
    }
}
