<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\DriverDatatableResource;
use App\Http\Resources\SuperAdmin\DriverVerificationResource;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\SuperAdmin\DriverResource;
use App\Models\Franchise;
use App\Models\Branch;
use App\Models\VehicleType;
use App\Models\UserDriver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'tab' => ['sometimes', 'string', 'exists:vehicle_types,name'],
            'type' => ['sometimes', 'string', Rule::in(['franchise', 'branch'])],
            'franchise' => ['sometimes', 'nullable', 'array'],
            'branches' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'retired', 'suspended'])],
        ]);

        // 2. Set defaults
        $filters = [
            'tab' => $validated['tab'] ?? 'taxi',
            'type' => $validated['type'] ?? 'franchise',
            'franchise' => $validated['franchise'] ?? [],
            'branches' => $validated['branches'] ?? [],
            'status' => $validated['status'] ?? 'active',
        ];

        // 3. Build and execute query
        $query = $this->buildBaseQuery($filters);
        $drivers = $query->get();

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

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/fleet/DriverIndex', [
            'drivers' => DriverDatatableResource::collection($drivers),
            'franchises' => $franchiseList,
            'branches' => $branchList,
            'vehicleTypes' => fn () => VehicleType::select('id', 'name')->orderBy('id', 'asc')->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function verification(Request $request): Response
    {
        // 1. Validate all filters
        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(['inactive', 'available'])],
        ]);

        // 2. Set defaults
        $filters = [
            'status' => $validated['status'] ?? 'inactive',
        ];

        // 3. Build and execute query
        $query = UserDriver::with([
            'user:id,username,email,phone',
            'status:id,name',
        ])->whereHas('status', fn ($q) => $q->where('name', $filters['status']));
        $drivers = $query->get();

        // 4. Return all data to Inertia
        return Inertia::render('super-admin/fleet/DriverVerification', [
            'drivers' => DriverVerificationResource::collection($drivers),
            'franchises' => fn () => Franchise::select('id', 'name')->get(),
            'filters' => [
                'status' => $filters['status'],
            ],
        ]);
    }

    /**
     * Creates the base query with all "WHERE" conditions.
     */
    private function buildBaseQuery(array $filters): Builder
    {
        $query = UserDriver::with([
            'user:id,username,email,phone',
            'status:id,name',
        ])->whereHas('status', fn ($q) => $q->where('name', $filters['status']
        ))->whereHas('vehicleTypes', fn ($q) => $q->where('name', $filters['tab']));

        if ($filters['type'] === 'branch') {
            // Filter by specific branches
            $query->whereHas('branches', function ($q) use ($filters) {
                $q->when(!empty($filters['branches']), fn ($subQ) => 
                    $subQ->whereIn('branches.id', $filters['branches'])
                );
                // If no branches selected, but franchises are, ensure branches belong to those franchises
                $q->when(empty($filters['branches']) && !empty($filters['franchise']), fn ($subQ) =>
                    $subQ->whereIn('franchise_id', $filters['franchise'])
                );
            })->with('branches:id,name');
        } else {
            // Filter by Franchises
            $query->whereHas('franchises', function ($q) use ($filters) {
                $q->when(!empty($filters['franchise']), fn ($subQ) =>
                    $subQ->whereIn('franchises.id', $filters['franchise'])
                );
            });
        }

        // Eager load franchises to make name available in the resource
        return $query->with('franchises:id,name');
    }

    public function verify(UserDriver $driver)
    {
        $availableStatus = Status::where('name', 'available')->firstOrFail();
        $faker = Faker::create();

        DB::transaction(function () use ($driver, $availableStatus, $faker) {
            // update driver status and verified
            $driver->status_id = $availableStatus->id;
            $driver->is_verified = true;
            // generate code number and check for uniqueness
            do {
                $code = $faker->unique()->bothify('??-####');
            } while (UserDriver::where('code_number', $code)->exists());

            $driver->code_number = $code;
            $driver->save();
        });

        return back();
    }

    public function show(UserDriver $driver)
    {
        // Load relationships and return as JSON
        $driver->loadMissing(['user:id,username,name,email,phone,gender,address,region,city,barangay,province,postal_code', 'status:id,name']);

        return new DriverResource($driver);
    }
}
