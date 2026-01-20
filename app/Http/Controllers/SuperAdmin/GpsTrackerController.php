<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\GpsTrackerResource;
use App\Models\Franchise;
use App\Models\UserDriver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GpsTrackerController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'franchise' => ['sometimes', 'nullable', 'array'], 
            'driver' => ['sometimes', 'nullable', 'array'],
        ]);

        $filters = [
            'franchise' => $validated['franchise'] ?? [],
            'driver' => $validated['driver'] ?? [],
        ];

        // 1. Fetch Map Data
        $mapRoutes = $this->getOnlineDriverLocations($filters);
        $driversList = $this->getContextualDrivers($filters);

        return Inertia::render('super-admin/fleet/GpsTracker', [
            'mapMarkers' => GpsTrackerResource::collection($mapRoutes),
            'franchises' => fn () => Franchise::select('id', 'name')->get(),
            'drivers' => fn () => $driversList,
            'filters' => $filters,
        ]);
    }

    private function getOnlineDriverLocations(array $filters)
    {
        $query = UserDriver::query()
            ->whereHas('status', fn ($q) => $q->where('name', 'active'))
            ->whereHas('vehicles', fn ($q) => $q->whereHas('status', fn ($subQ) => $subQ->where('name', 'active')))
            ->where(function ($q) {
                $q->whereNotNull('longitude')
                ->whereNotNull('latitude');
            })
            ->with([
                'user:id,username',
                'vehicles:id,driver_id,plate_number',  
            ]);

        // Filter by specific driver if selected
        $query->when(!empty($filters['driver']), function ($q) use ($filters) {
            $q->whereIn('id', $filters['driver']);
        });

        // Filter by specific franchise if selected
        $query->whereHas('franchises', function ($q) use ($filters) {
            $q->when($filters['franchise'], fn ($subQ) =>
                $subQ->whereIn('franchises.id', $filters['franchise'])
            );
        })->with('franchises:id,name');

        return $query->get();
    }

    /**
     * Efficiently fetches drivers based on the current view context
     */
    private function getContextualDrivers(array $filters)
    {
        // Start with UserDriver and join the base User table to get username
        $query = UserDriver::query()
            ->join('users', 'user_drivers.id', '=', 'users.id')
            ->select('user_drivers.id', 'users.username');

        if (!empty($filters['franchise'])) {
            // Get drivers strictly belonging to this franchise
            $query->whereHas('franchises', function ($q) use ($filters) {
                $q->whereIn('franchises.id', $filters['franchise']);
            });
        } else {
            // Get ALL drivers that belong to ANY franchise
            $query->has('franchises');
        }

        return $query->orderBy('users.username')->get();
    }
}
