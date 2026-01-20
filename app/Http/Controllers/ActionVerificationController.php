<?php

namespace App\Http\Controllers;

use App\Models\ActionVerification;
use App\Notifications\VerificationActionOtp;
use Illuminate\Http\Request;

class ActionVerificationController extends Controller
{
    public function sendActionCode(Request $request)
    {
        $request->validate([
            'action' => 'required|string',
        ]);

        $user = $request->user();
        $action = $request->action;

        // Generate 6-digit code
        $code = rand(100000, 999999);

        // Save code
        ActionVerification::create([
            'user_id' => $user->id,
            'action' => $action,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Send SMS
        $user->notify(new VerificationActionOtp($code));

        return response()->json([
            'message' => '2FA code sent via SMS',
            'expires_at' => now()->addMinutes(5)->toDateTimeString(),
        ]);
    }
}
