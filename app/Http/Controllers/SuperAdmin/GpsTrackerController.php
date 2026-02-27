<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\GpsTrackerResource;
use App\Models\Franchise;
use App\Models\Branch;
use App\Models\Status;
use App\Models\UserDriver;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GpsTrackerController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'tab' => ['sometimes', 'string', 'exists:vehicle_types,name'],
            'type' => ['sometimes', 'string', Rule::in(['franchise', 'branch'])],
            'franchises' => ['sometimes', 'nullable', 'array'],
            'branches' => ['sometimes', 'nullable', 'array'],
            'drivers' => ['sometimes', 'nullable', 'array'],
        ]);

        $filters = [
            'tab' => $validated['tab'] ?? 'taxi',
            'type' => $validated['type'] ?? 'franchise',
            'franchises' => $validated['franchises'] ?? [],
            'branches' => $validated['branches'] ?? [],
            'drivers' => $validated['drivers'] ?? [],
        ];

        // 1. Fetch Map Data
        $mapRoutes = $this->getOnlineDriverLocations($filters);
        $driversList = $this->getContextualDrivers($filters);

        $activeStatusId = Status::where('name', 'active')->value('id');

        $franchiseList = Franchise::select('id', 'name')
            ->whereHas('vehicleTypes', function ($q) use ($activeStatusId, $filters) {
                $q->where('vehicle_types.name', $filters['tab'])
                ->where('franchise_vehicle_type.status_id', $activeStatusId);
            })
            ->get();
            
        $branchList = Branch::select('id', 'name', 'franchise_id')
            ->whereHas('franchise.vehicleTypes', function ($q) use ($activeStatusId, $filters) {
                $q->where('vehicle_types.name', $filters['tab'])
                ->where('franchise_vehicle_type.status_id', $activeStatusId);
            })->when(!empty($filters['franchises']), function ($q) use ($filters) {
                $q->whereIn('franchise_id', $filters['franchises']);
            })
            ->get();

        return Inertia::render('super-admin/fleet/GpsTracker', [
            'mapMarkers' => GpsTrackerResource::collection($mapRoutes),
            'franchises' => fn () => $franchiseList,
            'branches' => fn () => $branchList,
            'vehicleTypes' => fn () => VehicleType::select('id', 'name')->orderBy('id', 'asc')->get(),
            'drivers' => fn () => $driversList,
            'filters' => $filters,
        ]);
    }

    private function getOnlineDriverLocations(array $filters)
    {
        $query = UserDriver::query()
            ->whereHas('status', fn ($q) => $q->where('name', 'active'))
            ->whereHas('vehicles', function ($q) use ($filters) {
                $q->whereHas('status', fn ($subQ) => $subQ->where('name', 'active'))

                ->whereHas('vehicleType', function ($typeQ) use ($filters) {
                    $typeQ->where('name', $filters['tab']);
                });
            })
            ->where(function ($q) {
                $q->whereNotNull('longitude')
                ->whereNotNull('latitude');
            })
            ->with([
                'user:id,username',
                'vehicles:id,driver_id,plate_number,vehicle_type_id',
                'vehicles.vehicleType:id,name',
            ]);

        // Filter by specific driver if selected
        $query->when(!empty($filters['drivers']), function ($q) use ($filters) {
            $q->whereIn('id', $filters['drivers']);
        });

        if ($filters['type'] === 'franchise') {
            // Filter by specific franchise if selected
            $query->whereHas('franchises', function ($q) use ($filters) {
                $q->when($filters['franchises'], fn ($subQ) =>
                    $subQ->whereIn('franchises.id', $filters['franchises'])
                );
            })->with('franchises:id,name');
        } else {
            // Filter by specific branch if selected
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->when($filters['branches'], fn ($subQ) =>
                    $subQ->whereIn('branches.id', $filters['branches'])
                );
            })->with('branches:id,name');
        }

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
            ->select('user_drivers.id', 'users.username')
            ->whereHas('vehicles', function ($q) use ($filters) {
                $q->whereHas('vehicleType', function ($typeQ) use ($filters) {
                    $typeQ->where('name', $filters['tab']);
                });
            });

        if ($filters['type'] === 'franchise') {
            if (!empty($filters['franchises'])) {
                // Get drivers strictly belonging to this franchise
                $query->whereHas('franchises', function ($q) use ($filters) {
                    $q->whereIn('franchises.id', $filters['franchises']);
                });
            } else {
                // Get ALL drivers that belong to ANY franchise
                $query->has('franchises');
            }
        } else {
            if (!empty($filters['branches'])) {
                // Get drivers strictly belonging to this branch
                $query->whereHas('branches', function ($q) use ($filters) {
                    $q->whereIn('branches.id', $filters['branches']);
                });
            } else {
                // Get ALL drivers that belong to ANY branch
                $query->has('branches');
            }
        }

        return $query->orderBy('users.username')->get();
    }
}
