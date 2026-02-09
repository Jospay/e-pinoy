<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBoundaryContractRequest;
use App\Models\BoundaryContract;
use App\Models\Status;
use App\Models\VehicleType;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BoundaryContractController extends Controller
{
    public function index()
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        $franchiseId = $franchise?->id;

        $query = BoundaryContract::with(['driver.user', 'franchise', 'vehicleTypes'])
            ->when($franchiseId, fn ($q) => $q->where('franchise_id', $franchiseId))
            ->orderByDesc('created_at');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('coverage_area', 'like', "%{$search}%")
                ->orWhereHas('franchise', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        $contracts = $query->paginate(10)
        ->through(fn ($contract) => [
            'id' => $contract->id,
            'name' => $contract->name,
            'rates' => $contract->vehicleTypes->map(fn($vt) => [
                'type' => $vt->name,
                'amount' => $vt->pivot->amount,
                'status' => optional(Status::find($vt->pivot->status_id))->name ?? 'pending',
            ]),
            'amount' => $contract->vehicleTypes->first()?->pivot->amount ?? '0.00',
            'status' => optional(Status::find($contract->vehicleTypes->first()?->pivot->status_id))->name ?? 'pending',

            'driver_email' => $contract->driver?->user->email,
            'driver_phone' => $contract->driver?->user->phone,
            'coverage_area' => $contract->coverage_area,
            'contract_terms' => $contract->contract_terms,
            'start_date' => $contract->start_date,
            'end_date' => $contract->end_date,
            'driver_username' => $contract->driver?->user->username,
            'franchise' => $contract->franchise?->name,
            'franchise_email' => $contract->franchise?->email,
            'franchise_phone' => $contract->franchise?->phone,
        ]);

        return Inertia::render('owner/boundary-contracts/Index', [
            'contracts' => $contracts,
        ]);
    }

    public function create()
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        if (!$franchise) {
            abort(404, 'Franchise not found');
        }

        $drivers = $franchise->drivers()
            ->whereDoesntHave('boundaryContracts')
            ->with(['user', 'vehicleTypes']) // 👈 important
            ->get()
            ->map(fn($driver) => [
                'id' => $driver->id,
                'username' => $driver->user?->username,
                'vehicle_types' => $driver->vehicleTypes->map(fn($vt) => [
                    'id' => $vt->id,
                    'name' => $vt->name,
                ]),
            ]);

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

        // ✅ backend default status
        $activeStatusId = Status::where('name', 'active')->value('id');

        $syncData = [];
        foreach ($request->vehicle_rates as $rate) {
            $syncData[$rate['vehicle_type_id']] = [
                'amount'    => $rate['amount'],
                'status_id' => $activeStatusId,
            ];
        }

        $contract->vehicleTypes()->attach($syncData);

        return redirect()->route('owner.boundary-contracts.index');
    });
}

}
