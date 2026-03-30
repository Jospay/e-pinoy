<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBoundaryContractRequest;
use App\Models\BoundaryContract;
use App\Models\Status;
use App\Models\VehicleType;
use App\Models\UserDriver;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BoundaryContractController extends Controller
{
    public function index(Request $request)
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        if (!$franchise) abort(404, 'Franchise not found');

        $franchiseVehicleTypes = VehicleType::whereHas('franchises', function ($q) use ($franchise) {
            $q->where('franchise_id', $franchise->id);
        })->get();

        $selectedType = $request->vehicle_type ?: $franchiseVehicleTypes->first()?->name;

        $query = BoundaryContract::with(['driver.user', 'driver.branches', 'franchise', 'vehicleTypes'])
            ->where('franchise_id', $franchise->id);

        $query->when($request->search, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhereHas('driver.user', fn($q2) => $q2->where('username', 'like', "%{$search}%"));
            });
        });

        $query->when($request->status, function ($q, $status) {
            if ($status !== 'all') {
                $q->whereHas('vehicleTypes', function ($vt) use ($status) {
                    $vt->join('statuses', 'boundary_contract_vehicle_type.status_id', '=', 'statuses.id')
                       ->where('statuses.name', $status);
                });
            }
        });

        $query->when($request->branch_id, function ($q, $branchId) {
            $q->whereHas('driver', function ($sub) use ($branchId) {
                if ($branchId === 'franchise') {
                    $sub->whereDoesntHave('branches');
                } elseif ($branchId === 'only_branches') {
                    $sub->whereHas('branches');
                } elseif ($branchId !== 'all') {
                    $sub->whereHas('branches', fn($b) => $b->where('branches.id', $branchId));
                }
            });
        });

        $query->when($selectedType, function ($q, $type) {
            $q->whereHas('vehicleTypes', fn($vt) => $vt->where('name', $type));
        });

        $allStatuses = Status::pluck('name', 'id');

        $contracts = $query->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($contract) use ($allStatuses) {
                $pivotData = $contract->vehicleTypes->first()?->pivot;
                $statusName = $pivotData && isset($allStatuses[$pivotData->status_id])
                    ? $allStatuses[$pivotData->status_id]
                    : 'Unknown';

                $branch = $contract->driver?->branches->first();

                return [
                    'id' => $contract->id,
                    'name' => "Contract: {$contract->name}",
                    'amount' => number_format($pivotData->amount ?? 0, 2),
                    'status_name' => $statusName,
                    'driver_username' => $contract->driver?->user->username ?? 'N/A',
                    'driver_email' => $contract->driver?->user->email ?? 'N/A',
                    'driver_phone' => $contract->driver?->user->phone ?? 'N/A',
                    'branch_name' => $branch ? $branch->name : ($contract->franchise?->name ?? 'Main Franchise'),
                    'branch_email' => $branch ? $branch->email : ($contract->franchise?->email ?? 'N/A'),
                    'branch_phone' => $branch ? $branch->phone : ($contract->franchise?->phone ?? 'N/A'),
                    'is_branch' => (bool)$branch,
                    'vehicle_type_name' => $contract->vehicleTypes->first()?->name ?? 'N/A',
                    'coverage_area' => $contract->coverage_area,
                    'contract_terms' => $contract->contract_terms,
                    'renewal_terms' => $contract->renewal_terms,
                    'start_date' => $contract->start_date,
                    'end_date' => $contract->end_date,
                ];
            });

        return Inertia::render('owner/boundary-contracts/Index', [
            'contracts' => $contracts,
            'branches' => $franchise->branches,
            'franchiseVehicleTypes' => $franchiseVehicleTypes,
            'statuses' => Status::whereIn('name', ['Pending', 'Active', 'Expired', 'Terminated'])->get(),
            'filters' => [
                'search' => $request->search,
                'status' => $request->status ?? 'all',
                'branch_id' => $request->branch_id ?? 'all',
                'vehicle_type' => $selectedType,
            ],
        ]);
    }

    public function create()
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        if (!$franchise) abort(404, 'Franchise not found');

        $drivers = UserDriver::with(['user', 'vehicleTypes'])
            ->whereHas('status', fn($q) => $q->where('name', 'Approved'))
            ->whereDoesntHave('boundaryContracts')
            ->whereHas('franchises', fn($f) => $f->where('franchises.id', $franchise->id))
            ->whereDoesntHave('branches')
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'username' => "{$driver->user?->username} (Main Franchise)",
                    'vehicle_types' => $driver->vehicleTypes->map(fn($vt) => ['id' => $vt->id, 'name' => $vt->name]),
                ];
            });

        $vehicles = Vehicle::where('franchise_id', $franchise->id)
            ->whereNull('branch_id')
            ->whereNull('driver_id')
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'vehicle_type_id' => $v->vehicle_type_id,
                    'label' => "{$v->plate_number} - {$v->brand} {$v->model}",
                ];
            });

        return Inertia::render('owner/boundary-contracts/Create', [
            'drivers' => $drivers,
            'vehicles' => $vehicles,
            'vehicleTypes' => VehicleType::all(),
        ]);
    }

    public function store(StoreBoundaryContractRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $franchise = auth()->user()->ownerDetails?->franchises()->first();

            // Get the ID for 'Active' status
            $activeStatusId = Status::where('name', 'Active')->value('id');

            // 1. Create the Contract
            $contract = BoundaryContract::create([
                'franchise_id'   => $franchise->id,
                'driver_id'      => $request->driver_id,
                'name'           => $request->name,
                'start_date'     => $request->start_date,
                'end_date'       => $request->end_date,
                'coverage_area'  => $request->coverage_area,
                'contract_terms' => $request->contract_terms,
                'renewal_terms'  => $request->renewal_terms,
                'currency'       => 'PHP',
            ]);

            // 2. Update the Vehicle: Assign driver and mark as Active
            Vehicle::where('id', $request->vehicle_id)->update([
                'driver_id' => $request->driver_id,
                'status_id' => $activeStatusId // Update vehicle status
            ]);

            // 3. Update the Driver: Set their account status to Active
            UserDriver::where('id', $request->driver_id)->update([
                'status_id' => $activeStatusId
            ]);

            // 4. Attach Vehicle Rates for the contract
            foreach ($request->vehicle_rates as $rate) {
                $contract->vehicleTypes()->attach($rate['vehicle_type_id'], [
                    'amount'    => $rate['amount'],
                    'status_id' => $activeStatusId,
                ]);
            }

            return redirect()->route('owner.boundary-contracts.index');
        });
    }
}
