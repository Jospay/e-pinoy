<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\StoreBoundaryContractRequest;
use App\Models\BoundaryContract;
use App\Models\Status;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BoundaryContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branch = auth()->user()->managerDetails?->branches()->first();
        $branchId = $branch?->id;

        $query = BoundaryContract::with(['status', 'branch'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at');

        // Filter by status
        if ($status = request('status')) {
            if ($status !== 'all') {
                $query->whereHas('status', fn($q) => $q->where('name', $status));
            }
        }

        // Global search
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('coverage_area', 'like', "%{$search}%")
                ->orWhereHas('branch', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        $contracts = $query->paginate(10)
            ->through(fn ($contract) => [
                'id' => $contract->id,
                'name' => $contract->name,
                'amount' => $contract->amount,
                'coverage_area' => $contract->coverage_area,
                'contract_terms' => $contract->contract_terms,
                'renewal_terms' => $contract->renewal_terms,
                'start_date' => $contract->start_date,
                'end_date' => $contract->end_date,
                'driver_username' => $contract->driver?->user->username,
                'driver_email' => $contract->driver?->user->email,
                'driver_phone' => $contract->driver?->user->phone,
                'status' => $contract->status?->name,
                'branch' => $contract->branch?->name,
                'branch_email' => $contract->branch?->email,
                'branch_phone' => $contract->branch?->phone,
            ]);

        return Inertia::render('manager/boundary-contracts/Index', [
            'contracts' => $contracts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $branch = auth()->user()->managerDetails?->branches()->first();

        if (!$branch) {
            abort(404, 'Branch not found');
        }

        // Vehicles in this branch
        $vehicles = $branch->vehicles()
            ->whereDoesntHave('boundaryContracts')
            ->whereDoesntHave('driver')
            ->with(['status'])
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'vin' => $vehicle->vin,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'color' => $vehicle->color,
                    'year' => $vehicle->year,
                    'status_id' => $vehicle->status_id,
                    'status_name' => $vehicle->status->name,
                ];
            });

        // Drivers in this branch who are NOT assigned to any vehicle
        $drivers = $branch->drivers()
            ->whereDoesntHave('boundaryContracts')
            ->whereDoesntHave('vehicles')
            ->with('user')
            ->get()
            ->map(fn($driver) => [
                'id' => $driver->id,
                'username' => $driver->user?->username,
                'email' => $driver->user?->email,
                'phone' => $driver->user?->phone,
            ]);

        return Inertia::render('manager/boundary-contracts/Create', [
            'vehicles' => $vehicles,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBoundaryContractRequest $request)
    {
        DB::transaction(function () use ($request) {

            $pendingStatusId = Status::where('name', 'pending')->firstOrFail()->id;
            $branch = auth()->user()->managerDetails?->branches()->first();
            $branchId = $branch?->id;

            BoundaryContract::create([
                'status_id' => $pendingStatusId,
                'branch_id' => $branchId,
                'driver_id' => $request->driver,
                'vehicle_id' => $request->vehicle,
                'name' => $request->name,
                'amount' => $request->amount,
                'currency' => 'PHP',
                'coverage_area' => $request->coverage_area,
                'contract_terms' => $request->contract_terms,
                'renewal_terms' => $request->renewal_terms,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]);

            Vehicle::where('id', $request->vehicle)->update(['driver_id' => $request->driver]);
        });

        return to_route('manager.boundary-contracts.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
