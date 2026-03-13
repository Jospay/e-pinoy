<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\EWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

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

        // Format: Replace leading 0 with 63
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
                // Using 'success' so your Vue watcher picks it up
                return back()->with('success', 'OTP sent to your registered number ending in ' . substr($cleanPhone, -4));
            }

            Log::error("Movider API Error: " . $response->body());
            return back()->withErrors(['otp' => 'SMS provider error. Please check logs.']);

        } catch (\Exception $e) {
            Log::error("OTP Send Exception: " . $e->getMessage());
            return back()->withErrors(['otp' => 'Failed to send SMS.']);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['code' => 'required|numeric']);
        $storedOtp = Session::get('otp_code');
        $expiresAt = Session::get('otp_expires_at');

        if ($storedOtp && $request->code == $storedOtp && now()->lt($expiresAt)) {
            EWallet::where('user_id', auth()->id())
                ->update(['last_otp_verified_at' => now()]);

            Session::forget(['otp_code', 'otp_expires_at']);

            return redirect()->route('passenger.transactionhisory', ['status' => 'paid'])
                             ->with('success', 'Security verified! You can now click Refund again.');

        }

        return back()->withErrors(['code' => 'The code is incorrect or has expired.']);
    }
}
