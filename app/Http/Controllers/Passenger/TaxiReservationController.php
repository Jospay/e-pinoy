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

    public function index(Request $request, Reservation $reservation)
    {
        $bookingType = $request->query('type', 'after');

        return Inertia::render('passenger/dashboard/TaxiReservation', [
            'busReservation' => $reservation->load(['fromStation', 'toStation']),
            'bookingType'    => $bookingType,
            'defaultPickup'  => $bookingType === 'after' ? $reservation->toStation?->name : '',
            'defaultDest'    => $bookingType === 'before' ? $reservation->fromStation?->name : '',
            'passengerCount' => $reservation->passenger_count,
            'walletBalance'  => (float) (auth()->user()->eWallet?->amount ?? 0),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reservation_id'       => 'required|exists:reservations,id',
            'booking_type'         => 'required|in:before,after',
            'time_pickup'          => 'nullable|required_if:booking_type,before',
            'passenger_count'      => 'required|integer',
            'amount'               => 'required|numeric|min:50',
            'pickup_loc_name'      => 'required|string',
            'destination_loc_name' => 'required|string',
            'start_lat'            => 'required|numeric',
            'start_lng'            => 'required|numeric',
            'end_lat'              => 'required|numeric',
            'end_lng'              => 'required|numeric',
            'distance_km'          => 'required|numeric',
            'payment_options'      => 'required|string|in:Wallet,Online Payment',
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
                'reserve_date' => $busReservation->reserve_date,
                'qrcode_name'  => $refNumber
            ]);

            if ($validated['payment_options'] === 'Wallet') {
                $wallet = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);

                if ($wallet->amount < $validated['amount']) {
                    return back()->withErrors(['amount' => 'Insufficient wallet balance.']);
                }

                $wallet = EWallet::where('id', $wallet->id)->lockForUpdate()->first();
                $wallet->decrement('amount', $validated['amount']);

                $taxi = TaxiReservation::create(array_merge($taxiData, [
                    'status_id' => $paidStatusId,
                ]));

                TransactionHistory::create([
                    'e_wallet_id' => $wallet->id,
                    'old_amount'  => $wallet->amount + $validated['amount'],
                    'new_amount'  => $wallet->amount,
                    'description' => 'Taxi Booking Payment: ' . $taxi->qrcode_name,
                ]);

                DB::commit();
                return redirect()->route('passenger.reservationtaxi.success', $taxi->id);
            }

            // Online Payment
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
            return back()->withErrors(['amount' => 'An unexpected error occurred.']);
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
                    'payment_method_types' => ['card', 'paymaya', 'qrph', 'grab_pay', 'billease'],
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
