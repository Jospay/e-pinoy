<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
{
    [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

    $user = $request->user();
    $hasActiveVehicleType = false;
    $canAccessBus = false; // Initialize the variable

    if ($user && $user->user_type_id === 2) {
        $franchise = $user->ownerDetails?->franchises()->first();
        if ($franchise) {
            // General check (keeps your existing logic working)
            $hasActiveVehicleType = $franchise->vehicleTypes()
                ->where('status_id', 1)
                ->exists();

            // Specific check for BUS (Vehicle Type 2)
            $canAccessBus = $franchise->vehicleTypes()
                ->where('vehicle_type_id', 2)
                ->where('status_id', 1)
                ->exists();
        }
    }

    return [
        ...parent::share($request),
        'name' => config('app.name'),
        'quote' => ['message' => trim($message), 'author' => trim($author)],
        'auth' => [
            'user' => $user,
            'hasActiveVehicleType' => $hasActiveVehicleType,
            'canAccessBus' => $canAccessBus, // Add this line!
        ],
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error'   => fn () => $request->session()->get('error'),
        ],
        'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
    ];
}
}
