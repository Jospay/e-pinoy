<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\EWallet;
use App\Models\Reservation;
use App\Models\TaxiReservation;
use App\Models\TransactionHistory;
use App\Models\Status;
use App\Models\StationSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class OTPController extends Controller
{
    public function index($purpose = null)
    {
        return Inertia::render('passenger/dashboard/OTPVerify', [
            'purpose' => $purpose
        ]);
    }

    public function sendOtp(Request $request)
    {
        $user = auth()->user();
        $phone = $user->phone;

        if (!$phone) {
            return back()->withErrors(['otp' => 'No phone number associated with this account.']);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '63' . substr($cleanPhone, 1);
        }

        $otp = rand(100000, 999999);
        Session::put('otp_code', $otp);
        Session::put('otp_expires_at', now()->addMinutes(5));

        Log::info("OTP generated for User {$user->id}: {$otp}");

        try {
            $response = Http::asForm()->post('https://api.movider.co/v1/sms', [
                'api_key'    => env('MOVIDER_API_KEY'),
                'api_secret' => env('MOVIDER_API_SECRET'),
                'from'       => env('MOVIDER_SENDER_ID', 'E-Pinoy'),
                'to'         => $cleanPhone,
                'text'       => "Your E-Pinoy OTP code is: $otp. Valid for 5 minutes.",
            ]);

            if ($response->successful()) {
                return back()->with('success', 'OTP sent to ending in ' . substr($cleanPhone, -4));
            }

            Log::error("Movider API Error: " . $response->body());
            return back()->withErrors(['otp' => 'SMS provider error.']);
        } catch (\Exception $e) {
            Log::error("OTP Send Exception: " . $e->getMessage());
            return back()->withErrors(['otp' => 'Failed to send SMS.']);
        }
    }

    public function verifyOtp(Request $request)
{
    $request->validate([
        'code'      => 'required|numeric',
        'latitude'  => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'purpose'   => 'nullable|string'
    ]);

    $storedOtp = Session::get('otp_code');
    $expiresAt = Session::get('otp_expires_at');

    // 1. Validation Check
    if (!$storedOtp || $request->code != $storedOtp || now()->gt($expiresAt)) {
        return back()->withErrors(['code' => 'The code is incorrect or has expired.']);
    }

    $user = auth()->user();

    // 2. Mark security timestamp in DB
    EWallet::where('user_id', $user->id)->update(['last_otp_verified_at' => now()]);

    // Clear OTP from session immediately after success
    Session::forget(['otp_code', 'otp_expires_at']);

    // 3. RESUME FLOWS BASED ON SESSION DATA

    // FLOW A: Pending Bus or Taxi Reservation
    if (Session::has('pending_reservation')) {
        $data = Session::get('pending_reservation');

        // Inject the GPS coordinates from the OTP form into the data array
        $data['latitude'] = $request->latitude;
        $data['longitude'] = $request->longitude;

        // Determine if it's Taxi or Bus
        if (isset($data['booking_type'])) {
            return $this->processResumedTaxi($data);
        } else {
            return $this->processResumedReservation($data);
        }
    }

    // FLOW B: Pending Wallet Load (Top-up)
    if (Session::has('pending_wallet_amount')) {
        return redirect()->route('passenger.wallet.resume_after_otp', [
            'latitude'  => $request->latitude,
            'longitude' => $request->longitude
        ]);
    }

    // FLOW C: Refund Redirection
    // If user was doing a refund, we usually redirect back to history
    // where they can re-click "Refund" now that they are verified.
    if ($request->purpose === 'refund' || $request->query('purpose') === 'refund') {
        return redirect()->route('passenger.transactionhisory')
            ->with('success', 'Security verified. You can now proceed with your refund.');
    }

    // FLOW D: General Wallet Security Check
    if ($request->purpose === 'wallet' || $request->query('purpose') === 'wallet') {
        return redirect()->route('passenger.mywallet')->with('success', 'Security verified!');
    }

    // DEFAULT FALLBACK
    return redirect()->route('passenger.dashboard')
        ->with('success', 'Security identity verified successfully.');
}

    public function verifyLoadEwallet(Request $request)
    {
        // This remains as a secondary helper, but verifyOtp above now handles both flows.
        return $this->verifyOtp($request);
    }

    private function processResumedTaxi($data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $user = auth()->user();
                $wallet = EWallet::where('user_id', $user->id)->lockForUpdate()->first();

                // 1. Strict Status Lookup (Same as Bus flow)
                $paidStatus = Status::where('name', 'LIKE', '%Paid%')->first();

                if (!$paidStatus) {
                    throw new \Exception("The 'Paid' status is missing from the system. Please contact admin.");
                }

                $busRes = Reservation::findOrFail($data['reservation_id']);

                if ($wallet->amount < $data['amount']) {
                    throw new \Exception("Insufficient balance.");
                }

                $oldAmount = $wallet->amount;
                $newAmount = $oldAmount - $data['amount'];
                $refNumber = 'TXI-' . strtoupper(\Str::random(10));

                // 2. Security update
                $wallet->updateAmountAndSeal($newAmount);

                // 3. Create Taxi Reservation with guaranteed Paid ID
                $taxi = TaxiReservation::create([
                    'reservation_id' => $data['reservation_id'],
                    'passenger_id' => $user->id,
                    'vehicle_id' => 1, // Defaulting to 1 for generic taxi
                    'booking_type' => $data['booking_type'],
                    'time_pickup' => $data['time_pickup'] ?? null,
                    'passenger_count' => $data['passenger_count'],
                    'amount' => $data['amount'],
                    'pickup_loc_name' => $data['pickup_loc_name'],
                    'destination_loc_name' => $data['destination_loc_name'],
                    'start_lat' => $data['start_lat'],
                    'start_lng' => $data['start_lng'],
                    'end_lat' => $data['end_lat'],
                    'end_lng' => $data['end_lng'],
                    'distance_km' => $data['distance_km'],
                    'payment_options' => 'Wallet',
                    'status_id' => $paidStatus->id, // Fixed: No longer defaults to 1
                    'reserve_date' => Carbon::parse($busRes->reserve_date)->format('Y-m-d'),
                    'qrcode_name' => $refNumber
                ]);

                // 4. Log Transaction History with correct status
                TransactionHistory::create([
                    'e_wallet_id' => $wallet->id,
                    'status_id' => $paidStatus->id, // Added status tracking to history
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'type' => 'debit',
                    'description' => 'Taxi Booking Payment: ' . $taxi->qrcode_name . ' (Post-OTP Verified)',
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                ]);

                Session::forget('pending_reservation');

                return redirect()->route('passenger.reservationtaxi.success', $taxi->id);
            });
        } catch (\Exception $e) {
            Log::error("Taxi OTP Error: " . $e->getMessage());
            Session::forget('pending_reservation');
            return redirect()->route('passenger.dashboard')
                ->withErrors(['amount' => 'Taxi reservation failed: ' . $e->getMessage()]);
        }
}

    private function processResumedReservation($data)
{
    try {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $wallet = EWallet::where('user_id', $user->id)->lockForUpdate()->first();
            $sched = StationSchedule::findOrFail($data['station_schedule_id']);

            // Look for the specific status.
            $paidStatus = Status::where('name', 'LIKE', '%Paid%')->first();

            if (!$paidStatus) {
                throw new \Exception("The 'Paid' status is missing from the system. Please contact admin.");
            }

            if ($wallet->amount < $data['amount']) {
                throw new \Exception("Insufficient wallet balance.");
            }

            $oldAmount = $wallet->amount;
            $newAmount = $oldAmount - $data['amount'];
            $qrName = 'QR-' . strtoupper(\Str::random(12));

            $wallet->updateAmountAndSeal($newAmount);

            $reservation = Reservation::create([
                'vehicle_id' => $data['vehicle_id'],
                'passenger_id' => $user->id,
                'from_bus_station_id' => $data['from_bus_station_id'],
                'to_bus_station_id' => $data['to_bus_station_id'],
                'status_id' => $paidStatus->id, // Use the real ID
                'passenger_count' => $data['passenger_count'],
                'amount' => $data['amount'],
                'reserve_from_time' => $sched->from_time,
                'reserve_to_time' => $sched->to_time,
                'reserve_date' => $data['reserve_date'],
                'qrcode_name' => $qrName,
                'payment_options' => 'Wallet',
            ]);

            TransactionHistory::create([
                'e_wallet_id' => $wallet->id,
                'status_id' => $paidStatus->id, // Set status in history too
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'type' => 'debit',
                'description' => 'Bus Reservation ID: ' . $reservation->id . ' (Post-OTP Verified)',
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);

            \Session::forget('pending_reservation');

            return redirect()->route('passenger.reservation.success', [
                'reservation' => $reservation->qrcode_name
            ]);
        });
    } catch (\Exception $e) {
        Log::error("Post-OTP Reservation Error: " . $e->getMessage());
        \Session::forget('pending_reservation');
        return redirect()->route('passenger.dashboard')
            ->withErrors(['amount' => 'Reservation failed: ' . $e->getMessage()]);
    }
}
}
