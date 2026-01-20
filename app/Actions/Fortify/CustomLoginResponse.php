<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        $route = match ($user->userType->name) {
            'driver' => route('driver.dashboard'),
            'passenger' => route('passenger.dashboard'),
            'technician' => route('technician.dashboard'),
            'owner' => route('owner.dashboard'),
            'manager' => route('manager.dashboard'),
            'super_admin' => route('super-admin.dashboard'),
            default => route('home'),
        };

        return $request->wantsJson()
            ? response()->json(['two_factor' => false, 'redirect' => $route])
            : redirect($route);
    }
}
