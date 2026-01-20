<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInactiveStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->userType->name === 'super_admin') {
            return $next($request);
        }

        $status = $user->getStatusName();

        if ($status === 'active') {
            $route = match ($user->userType->name) {
                'driver' => route('driver.dashboard'),
                'passenger' => route('passenger.dashboard'),
                'technician' => route('technician.dashboard'),
                'owner' => route('owner.dashboard'),
                'super_admin' => route('super-admin.dashboard'),
                default => route('home'),
            };

            return $request->wantsJson()
                ? response()->json(['redirect' => $route])
                : redirect($route);
        }

        return $next($request);
    }
}
