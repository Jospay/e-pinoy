<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBoundaryContractRequest;
use App\Models\BoundaryContract;
use App\Models\Status;
use App\Models\VehicleType;
use App\Models\UserDriver;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BoundaryContractController extends Controller
{
    public function index(Request $request)
{
    $franchise = auth()->user()->ownerDetails?->franchises()->first();
    if (!$franchise) abort(404, 'Franchise not found');

    // 1. Get Franchise Vehicle Types first to determine the default tab
    $franchiseVehicleTypes = VehicleType::whereHas('franchises', function ($q) use ($franchise) {
        $q->where('franchise_id', $franchise->id);
    })->get();

    // 2. Set the default vehicle type if the request is empty
    // This matches the first tab that will be highlighted in Vue
    $selectedType = $request->vehicle_type ?: $franchiseVehicleTypes->first()?->name;

    $query = BoundaryContract::with(['driver.user', 'driver.branches', 'franchise', 'vehicleTypes'])
        ->where('franchise_id', $franchise->id);

    // Filter: Search
    $query->when($request->search, function ($q, $search) {
        $q->where(function ($sub) use ($search) {
            $sub->where('name', 'like', "%{$search}%")
                ->orWhereHas('driver.user', fn($q2) => $q2->where('username', 'like', "%{$search}%"));
        });
    });

    // Filter: Status
    $query->when($request->status, function ($q, $status) {
        if ($status !== 'all') {
            $q->whereHas('vehicleTypes', function ($vt) use ($status) {
                $vt->join('statuses', 'boundary_contract_vehicle_type.status_id', '=', 'statuses.id')
                   ->where('statuses.name', $status);
            });
        }
    });

    // Filter: Branch/Assignment
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

    // Filter: Vehicle Type (Always applied now, either from Request or Default)
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
                'name' => "Contract: {$contract->name}", // Added prefix per your detail request
                'amount' => number_format($pivotData->amount ?? 0, 2),
                'status_name' => $statusName,
                'driver_username' => $contract->driver?->user->username ?? 'N/A',
                'driver_email' => $contract->driver?->user->email ?? 'N/A',
                'driver_phone' => $contract->driver?->user->phone ?? 'N/A', // Adjusted to driver model
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
            'vehicle_type' => $selectedType, // Sending back the calculated default
        ],
    ]);
}

    public function create()
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        if (!$franchise) abort(404, 'Franchise not found');

        $branchIds = $franchise->branches()->pluck('id');

        // Fetch drivers that are EITHER in the franchise OR in its branches
        $drivers = UserDriver::with(['user', 'vehicleTypes', 'branches'])
            ->whereHas('status', fn($q) => $q->where('name', 'Approved'))
            ->whereDoesntHave('boundaryContracts') // Only drivers without contracts
            ->where(function ($query) use ($franchise, $branchIds) {
                $query->whereHas('franchises', fn($f) => $f->where('franchises.id', $franchise->id))
                      ->orWhereHas('branches', fn($b) => $b->whereIn('branches.id', $branchIds));
            })
            ->get()
            ->map(function ($driver) {
                $branchName = $driver->branches->first()?->name;
                $label = $branchName ? "($branchName)" : "(Main Franchise)";
                return [
                    'id' => $driver->id,
                    'username' => "{$driver->user?->username} {$label}",
                    'vehicle_types' => $driver->vehicleTypes->map(fn($vt) => ['id' => $vt->id, 'name' => $vt->name]),
                ];
            });

        return Inertia::render('owner/boundary-contracts/Create', [
            'drivers' => $drivers,
            'vehicleTypes' => VehicleType::all(),
            'statuses' => Status::all(),
        ]);
    }

    public function store(StoreBoundaryContractRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $franchise = auth()->user()->ownerDetails?->franchises()->first();

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

            $activeStatusId = Status::where('name', 'Active')->value('id');

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
