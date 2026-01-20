<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBoundaryContractRequest;
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
        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        $franchiseId = $franchise?->id;

        $query = BoundaryContract::with(['driver.user', 'status', 'franchise'])
            ->when($franchiseId, fn ($q) => $q->where('franchise_id', $franchiseId))
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
                ->orWhereHas('franchise', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
                // ->orWhereHas('branch', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
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
                'franchise' => $contract->franchise?->name,
                'franchise_email' => $contract->franchise?->email,
                'franchise_phone' => $contract->franchise?->phone,
                // 'branch' => $contract->branch?->name,
            ]);
        // $contracts = BoundaryContractResource::collection(
        //     $query->paginate(10)
        // );

        return Inertia::render('owner/boundary-contracts/Index', [
            'contracts' => $contracts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        if (!$franchise) {
            abort(404, 'Franchise not found');
        }

        // Vehicles in this franchise
        $vehicles = $franchise->vehicles()
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

        // Drivers in this franchise who are NOT assigned to any vehicle
        $drivers = $franchise->drivers()
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

        return Inertia::render('owner/boundary-contracts/Create', [
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
            $franchise = auth()->user()->ownerDetails?->franchises()->first();
            $franchiseId = $franchise?->id;

            BoundaryContract::create([
                'status_id' => $pendingStatusId,
                'franchise_id' => $franchiseId,
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

        return to_route('owner.boundary-contracts.index');
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
