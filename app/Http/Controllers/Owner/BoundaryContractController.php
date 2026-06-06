<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBoundaryContractRequest;
use App\Models\BoundaryContract;
use App\Models\Status;
use App\Models\TricycleTerminal;
use Illuminate\Support\Facades\Storage;
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

        $query = BoundaryContract::with(['driver.user', 'driver.branches', 'driver.tricycleTerminal', 'franchise', 'vehicleTypes'])
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
                    'toda_name' => $contract->driver?->tricycleTerminal?->name ?? 'No TODA',
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

        if (!$franchise) {
            abort(404, 'Franchise not found');
        }

        // Get all branch IDs belonging to this franchise to verify branch-assigned drivers
        $franchiseBranchIds = $franchise->branches()->pluck('id')->toArray();

        $drivers = UserDriver::with([
            'user',
            'vehicleTypes',
            'branches',
            'tricycleTerminal'
        ])
        ->whereHas('status', fn($q) => $q->where('name', 'Approved'))
        ->whereDoesntHave('boundaryContracts')
        ->where(function ($query) use ($franchise, $franchiseBranchIds) {
            // Include drivers directly linked to the main franchise
            $query->whereHas('franchises', fn($q) => $q->where('franchises.id', $franchise->id))
                  // OR drivers belonging to any branch under this franchise
                  ->orWhereHas('branches', fn($q) => $q->whereIn('branches.id', $franchiseBranchIds));
        })
        ->get()
        ->map(function ($driver) use ($franchise) {

            $branch = $driver->branches->first();

            return [
                'id' => $driver->id,
                'username' => $driver->user?->username,
                'branch_id' => $branch?->id,
                'branch_name' => $branch?->name,
                'assignment_name' => $branch ? $branch->name : $franchise->name,
                'vehicle_types' => $driver->vehicleTypes->map(fn($vt) => [
                    'id' => $vt->id,
                    'name' => $vt->name,
                ]),
                'prangkisa_attachment' => $driver->prangkisa_attachment
                    ? asset('storage/' . $driver->prangkisa_attachment)
                    : null,
            ];
        });

        $vehicles = Vehicle::where('franchise_id', $franchise->id)
            ->whereNull('driver_id')
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'vehicle_type_id' => $vehicle->vehicle_type_id,
                    'branch_id' => $vehicle->branch_id,
                    'label' => $vehicle->plate_number . ' - ' . $vehicle->brand . ' ' . $vehicle->model,
                ];
            });

        $terminals = TricycleTerminal::where('franchise_id', $franchise->id)
            ->get()
            ->map(function ($terminal) {
                return [
                    'id' => $terminal->id,
                    'name' => $terminal->name,
                    'branch_id' => $terminal->branch_id,
                ];
            });

        return Inertia::render('owner/boundary-contracts/Create', [
            'drivers' => $drivers,
            'vehicles' => $vehicles,
            'terminals' => $terminals,
            'vehicleTypes' => VehicleType::all(),
        ]);
    }

    public function store(StoreBoundaryContractRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $franchise = auth()->user()->ownerDetails?->franchises()->first();
            $activeStatusId = Status::where('name', 'Active')->value('id');

            // 1. Create the Contract
            $contract = BoundaryContract::create([
                'franchise_id'         => $franchise->id,
                'driver_id'            => $request->driver_id,
                'tricycle_terminal_id' => $request->filled('terminal_id') ? $request->terminal_id : null,
                'name'                 => $request->name,
                'start_date'           => $request->start_date,
                'end_date'             => $request->end_date,
                'coverage_area'        => $request->coverage_area,
                'contract_terms'       => $request->contract_terms,
                'renewal_terms'        => $request->renewal_terms,
                'currency'             => 'PHP',
            ]);

            // 2. Save Driver Terminal Assignment if present
            if ($request->filled('terminal_id')) {
                UserDriver::where('id', $request->driver_id)->update([
                    'tricycle_terminal_id' => $request->terminal_id,
                ]);
            }

            // 3. Save Prangkisa Attachment
            if ($request->hasFile('prangkisa_attachment')) {
                $path = $request->file('prangkisa_attachment')->store('prangkisa_attachment', 'public');

                UserDriver::where('id', $request->driver_id)->update([
                    'prangkisa_attachment' => $path,
                ]);
            }

            // 4. Update the Vehicle Assignment
            $vehicleUpdateFields = [
                'driver_id' => $request->driver_id,
                'status_id' => $activeStatusId
            ];

            if ($request->filled('terminal_id')) {
                $vehicleUpdateFields['tricycle_terminal_id'] = $request->terminal_id;
            }

            Vehicle::where('id', $request->vehicle_id)->update($vehicleUpdateFields);

            // 5. Update Driver Account Status
            UserDriver::where('id', $request->driver_id)->update([
                'status_id' => $activeStatusId
            ]);

            // 6. Attach Vehicle Rates to contract
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
