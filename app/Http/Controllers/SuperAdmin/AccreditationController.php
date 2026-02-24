<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\AccreditationDatatableResource;
use App\Models\Franchise;
use App\Models\Branch;
use App\Models\VehicleType;
use App\Models\Status;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccreditationController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'tab' => ['sometimes', 'string', 'exists:vehicle_types,name'],
            'franchises' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'pending', 'inactive'])],
        ]);

        // 2. Set defaults
        $filters = [
            'tab' => $validated['tab'] ?? 'taxi',
            'franchises' => $validated['franchises'] ?? [],
            'status' => $validated['status'] ?? 'active',
        ];

        // 3. Build and execute query
        $accreditations = $this->buildBaseQuery($filters)->get();
        // Pass status map to the resource class statically — 1 query, no N+1
        AccreditationDatatableResource::withStatusMap(
            Status::all()->keyBy('id')
        );

        $activeStatusId = Status::where('name', 'active')->value('id');

        $franchiseList = Franchise::select('id', 'name')
            ->whereHas('vehicleTypes', function ($q) use ($activeStatusId, $filters) {
                $q->where('vehicle_types.name', $filters['tab'])
                ->where('franchise_vehicle_type.status_id', $activeStatusId);
            })
            ->get();

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/fleet/AccreditationIndex', [
            'accreditations' => AccreditationDatatableResource::collection($accreditations),
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
        $activeFranchiseStatusId = Status::where('name', 'active')->value('id');
        $pivotStatusId = Status::where('name', $filters['status'])->value('id');

        $query = Franchise::with([
                'vehicleTypes' => function ($q) use ($filters, $pivotStatusId) {
                    $q->where('vehicle_types.name', $filters['tab'])
                    ->where('franchise_vehicle_type.status_id', $pivotStatusId)
                    ->withPivot('status_id');
                },
            ])
            ->where('status_id', $activeFranchiseStatusId)
            ->whereHas('vehicleTypes', function ($q) use ($filters, $pivotStatusId) {
                $q->where('vehicle_types.name', $filters['tab'])
                ->where('franchise_vehicle_type.status_id', $pivotStatusId);
            });

        if (!empty($filters['franchises'])) {
            $query->whereIn('franchises.id', $filters['franchises']);
        }

        return $query;
    }

    public function changeStatus(Request $request, Franchise $franchise)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'vehicle_type' => ['required', 'string', 'exists:vehicle_types,name'],
        ]);

        $vehicleType = VehicleType::where('name', $validated['vehicle_type'])->firstOrFail();
        $statusId = Status::where('name', $validated['status'])->value('id');

        // Update using the relationship
        $franchise->vehicleTypes()->updateExistingPivot($vehicleType->id, [
            'status_id' => $statusId,
        ]);

        return back();
    }
}
