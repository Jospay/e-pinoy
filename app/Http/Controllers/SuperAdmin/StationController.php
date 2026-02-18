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
use Inertia\Response;
use Illuminate\Validation\Rule;

class StationController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'franchises' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'pending', 'inactive'])],
        ]);

        // 2. Set defaults
        $filters = [
            'franchises' => $validated['franchises'] ?? [],
            'status' => $validated['status'] ?? 'active',
        ];

        // 3. Build and execute query
        $stations = $this->buildBaseQuery($filters)->get();

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/fleet/StationIndex', [
            'stations' => StationDatatableResource::collection($stations),
            'franchises' => fn () => Franchise::select('id', 'name')->get(),
            'vehicleTypes' => fn () => VehicleType::select('id', 'name')->orderBy('id', 'asc')->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * Creates the base query with all "WHERE" conditions.
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $activeFranchiseStatusId = Status::where('name', 'active')->value('id');
        $pivotStatusId = Status::where('name', $filters['status'])->value('id');
        $busVehicleTypeId = VehicleType::where('name', 'bus')->value('id');

        $query = Franchise::with([
                'vehicleTypes' => function ($q) use ($busVehicleTypeId, $pivotStatusId) {
                    $q->where('vehicle_types.id', $busVehicleTypeId)
                    ->where('franchise_vehicle_type.status_id', $pivotStatusId)
                    ->withPivot('status_id');
                },
                'busStations' => function ($q) {
                    $q->select('id', 'franchise_id', 'code_no', 'status_id')
                    ->with('status:id,name')
                    ->orderBy('id');
                },
            ])
            ->where('status_id', $activeFranchiseStatusId);

        if (!empty($filters['franchises'])) {
            $query->whereIn('franchises.id', $filters['franchises']);
        }

        return $query;
    }
}
