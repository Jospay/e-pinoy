<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\Branch;
use App\Models\BusStation;
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
            'type' => ['sometimes', 'string', Rule::in(['franchise', 'branch'])],
            'franchises' => ['sometimes', 'nullable', 'array'],
            'branches' => ['sometimes', 'nullable', 'array'],
        ]);

        // 2. Set defaults
        $filters = [
            'type' => $validated['type'] ?? 'franchise',
            'franchises' => $validated['franchises'] ?? [],
            'branches' => $validated['branches'] ?? [],
        ];

        // 3. Build and execute query
        $stations = $this->buildBaseQuery($filters)->get();

        $activeStatusId = Status::where('name', 'active')->value('id');

        $franchiseList = Franchise::select('id', 'name')
            ->whereHas('vehicleTypes', function ($q) use ($activeStatusId) {
                $q->where('vehicle_types.name', 'bus')
                ->where('franchise_vehicle_type.status_id', $activeStatusId);
            })
            ->get();
        
        $branchList = Branch::select('id', 'name', 'franchise_id')
            ->whereHas('franchise.vehicleTypes', function ($q) use ($activeStatusId) {
                $q->where('vehicle_types.name', 'bus')
                ->where('franchise_vehicle_type.status_id', $activeStatusId);
            })->when(!empty($filters['franchises']), function ($q) use ($filters) {
                $q->whereIn('franchise_id', $filters['franchises']);
            })
            ->get();

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/fleet/StationIndex', [
            'stations' => StationDatatableResource::collection($stations),
            'franchises' => fn () => $franchiseList,
            'branches' => fn () => $branchList,
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

        if ($filters['type'] === 'franchise') {
            $query = Franchise::where('status_id', $activeStatusId)
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
        } else {
            $query = Branch::where('status_id', $activeStatusId)
                ->whereHas('franchise.vehicleTypes', function ($q) use ($busVehicleTypeId, $activeStatusId) {
                    $q->where('vehicle_types.id', $busVehicleTypeId)
                    ->where('franchise_vehicle_type.status_id', $activeStatusId);
                })
                ->with([
                    'busStations' => function ($q) {
                        $q->select('id', 'branch_id', 'code_no', 'status_id')
                        ->with('status:id,name');
                    },
                    'franchise:id,name'
                ])
                ->when(!empty($filters['branches']), function ($query) use ($filters) {
                    $query->whereIn('id', $filters['branches']);
                });
        }

        return $query;
    }

    public function show(Request $request, int $id)
    {
        $type = $request->query('type', 'franchise');

        if ($type === 'branch') {
            $model = Branch::findOrFail($id);
            $model->loadMissing([
                'busStations' => function ($q) {
                    $q->select('id', 'branch_id', 'name', 'code_no', 'latitude', 'longitude', 'status_id')
                    ->with(['status:id,name', 'fromAmounts.toStation:id,code_no'])
                    ->orderBy('id');
                },
                'franchise:id,name',
            ]);
        } else {
            $model = Franchise::findOrFail($id);
            $model->loadMissing([
                'busStations' => function ($q) {
                    $q->select('id', 'franchise_id', 'name', 'code_no', 'latitude', 'longitude', 'status_id')
                    ->with(['status:id,name', 'fromAmounts.toStation:id,code_no'])
                    ->orderBy('id');
                },
            ]);
        }

        return new StationShowResource($model);
    }

    public function updateStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'station_id' => ['required', 'integer', 'exists:bus_stations,id'],
            'status'     => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $station = BusStation::where('id', $validated['station_id'])
            ->where(function ($q) use ($id, $request) {
                if ($request->query('type') === 'branch') {
                    $q->where('branch_id', $id);
                } else {
                    $q->where('franchise_id', $id);
                }
            })
            ->firstOrFail();

        if ($station->status_id === Status::where('name', $validated['status'])->value('id')) {
            return back()->withErrors(['status' => 'Station already has this status.']);
        }

        $station->update([
            'status_id' => Status::where('name', $validated['status'])->value('id'),
        ]);

        return back();
    }
}
