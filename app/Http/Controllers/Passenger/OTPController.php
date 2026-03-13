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
    public function index()
    {
        return Inertia::render('passenger/dashboard/OTPVerify');
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
            'code' => 'required|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $storedOtp = Session::get('otp_code');
        $expiresAt = Session::get('otp_expires_at');

        if ($storedOtp && $request->code == $storedOtp && now()->lt($expiresAt)) {
            $user = auth()->user();

            // 1. Update verification timestamp to bypass OTP for the next 7 days
            EWallet::where('user_id', $user->id)->update(['last_otp_verified_at' => now()]);
            Session::forget(['otp_code', 'otp_expires_at']);

            // 2. Branching Logic
            if (Session::has('pending_reservation')) {
                $data = Session::get('pending_reservation');
                $data['latitude'] = $request->latitude ?? ($data['latitude'] ?? null);
                $data['longitude'] = $request->longitude ?? ($data['longitude'] ?? null);

                // Check if it's a Taxi or Bus reservation (Taxi has 'booking_type')
                if (isset($data['booking_type'])) {
                    return $this->processResumedTaxi($data);
                } else {
                    return $this->processResumedReservation($data);
                }
            }

            // KEEPING YOUR REFUND REDIRECT
            return redirect()->route('passenger.transactionhisory', ['status' => 'paid'])
                             ->with('success', 'Security verified! You can now click Refund again.');
        }

        return back()->withErrors(['code' => 'The code is incorrect or has expired.']);
    }

    private function processResumedTaxi($data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $user = auth()->user();
                $wallet = EWallet::where('user_id', $user->id)->lockForUpdate()->first();
                $paidStatusId = Status::where('name', 'Paid')->first()->id;
                $busRes = Reservation::findOrFail($data['reservation_id']);

                if ($wallet->amount < $data['amount']) throw new \Exception("Insufficient balance.");

                $oldAmount = $wallet->amount;
                $newAmount = $oldAmount - $data['amount'];
                $refNumber = 'TXI-' . strtoupper(\Str::random(10));

                $wallet->updateAmountAndSeal($newAmount);

                $taxi = TaxiReservation::create([
                    'reservation_id' => $data['reservation_id'],
                    'passenger_id' => $user->id,
                    'vehicle_id' => 1,
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
                    'status_id' => $paidStatusId,
                    'reserve_date' => Carbon::parse($busRes->reserve_date)->format('Y-m-d'),
                    'qrcode_name' => $refNumber
                ]);

                TransactionHistory::create([
                    'e_wallet_id' => $wallet->id,
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
            return redirect()->route('passenger.dashboard')->withErrors(['amount' => $e->getMessage()]);
        }
    }

    private function processResumedReservation($data) // BUS FLOW
    {
        try {
            return DB::transaction(function () use ($data) {
                $user = auth()->user();
                $wallet = EWallet::where('user_id', $user->id)->lockForUpdate()->first();
                $sched = StationSchedule::findOrFail($data['station_schedule_id']);
                $paidStatusId = Status::where('name', 'Paid')->first()->id;

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
                    'status_id' => $paidStatusId,
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
                    'old_amount' => $oldAmount,
                    'new_amount' => $newAmount,
                    'type' => 'debit',
                    'description' => 'Bus Reservation ID: ' . $reservation->id . ' (Post-OTP Verified)',
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                ]);

                Session::forget('pending_reservation');

                return Inertia::render('passenger/dashboard/Success', [
                    'reservation' => $reservation->load(['fromStation', 'toStation', 'passenger', 'status', 'vehicle'])
                ]);
            });
        } catch (\Exception $e) {
            Log::error("Post-OTP Reservation Error: " . $e->getMessage());
            Session::forget('pending_reservation');
            return redirect()->route('passenger.dashboard')->withErrors(['amount' => 'Could not complete reservation: ' . $e->getMessage()]);
        }
    }
}
