<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasActiveVehicleType
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is owner
        if ($user && $user->user_type_id === 2) {
            $franchise = $user->ownerDetails?->franchises()->first();

            // Check if they have ANY vehicle type with status_id 1 (active)
            $hasActive = $franchise ? $franchise->vehicleTypes()
                ->where('status_id', 1)
                ->exists() : false;

            if (!$hasActive) {
                // Redirect to dashboard with a message if they try to access restricted pages
                return redirect()->route('owner.dashboard')
                    ->with('error', 'Please wait for admin approval of your vehicle types to access this feature.');
            }
        }

        return $next($request);
    }
}
