<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\VehicleType;
use App\Models\Status;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\SuperAdmin\StationDatatableResource;
use App\Http\Resources\SuperAdmin\StationShowResource;
use Inertia\Response;
use Illuminate\Validation\Rule;

class StationController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'franchises' => ['sometimes', 'nullable', 'array'],
        ]);

        // 2. Set defaults
        $filters = [
            'franchises' => $validated['franchises'] ?? [],
        ];

        // 3. Build and execute query
        $stations = $this->buildBaseQuery($filters)->get();

        $franchiseList = Franchise::select('id', 'name')
            ->whereHas('vehicleTypes', function ($q) {
                $q->where('vehicle_types.name', 'bus')
                ->where('franchise_vehicle_type.status_id', Status::where('name', 'active')->value('id'));
            })
            ->get();

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/fleet/StationIndex', [
            'stations' => StationDatatableResource::collection($stations),
            'franchises' => fn () => $franchiseList,
            'vehicleTypes' => fn () => VehicleType::select('id', 'name')->orderBy('id', 'asc')->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * Creates the base query with all "WHERE" conditions.
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $activeStatusId = Status::where('name', 'active')->value('id');
        $busVehicleTypeId = VehicleType::where('name', 'bus')->value('id');

        return Franchise::where('status_id', $activeStatusId)
            ->whereHas('vehicleTypes', function ($q) use ($busVehicleTypeId, $activeStatusId) {
                $q->where('vehicle_types.id', $busVehicleTypeId)
                ->where('franchise_vehicle_type.status_id', $activeStatusId);
            })
            ->with([
                'busStations' => function ($q) {
                    $q->select('id', 'franchise_id', 'code_no', 'status_id')
                    ->with('status:id,name');
                }
            ])
            ->when(!empty($filters['franchises']), function ($query) use ($filters) {
                $query->whereIn('id', $filters['franchises']);
            });
    }

    public function show(Franchise $franchise)
    {
        $franchise->loadMissing(['busStations' => function ($q) {
            $q->select('id', 'franchise_id', 'name', 'code_no', 'latitude', 'longitude', 'status_id')
            ->with([
                  'status:id,name',
                  'fromAmounts.toStation:id,code_no',
            ])
            ->orderBy('id');
        }]);

        return new StationShowResource($franchise);
    }
}
