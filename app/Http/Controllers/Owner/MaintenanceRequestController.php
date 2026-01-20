<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreMaintenanceRequest;
use App\Models\Maintenance;
use App\Models\Inventory;
use App\Models\Status;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MaintenanceRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $franchise = $this->getFranchiseOrDefault();
        $franchiseId = $franchise?->id;

        $query = Maintenance::with(['status', 'vehicle', 'inventory', 'technician',])
            ->when($franchiseId, function ($q) use ($franchiseId) {
                $q->whereHas('vehicle', function ($v) use ($franchiseId) {
                    $v->where('franchise_id', $franchiseId);
                });
            })
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($status = request('status')) {
            if ($status !== 'all') {
                $query->whereHas('status', fn($q) => $q->where('name', $status));
            }
        }

        // Global search
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {

                // Search maintenance fields
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('maintenance_type', 'like', "%{$search}%");

                // Search vehicle fields
                $q->orWhereHas('vehicle', function ($q2) use ($search) {
                    $q2->where(function ($q3) use ($search) {
                        $q3->where('plate_number', 'like', "%{$search}%")
                            ->orWhere('vin', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('color', 'like', "%{$search}%")
                            ->orWhere('year', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%");
                    });
                });
            });
        }

        // Paginate
        $requests = $query
            ->paginate(10)
            ->through(function ($request) {
                return [
                    'id' => $request->id,
                    'maintenance_type' => $request->maintenance_type,
                    'description' => $request->description,
                    'maintenance_date' => $request->maintenance_date,
                    'next_maintenance_date' => $request->next_maintenance_date,

                    'status' => $request->status?->name,

                    'technician' => $request->technician ? [
                        'id' => $request->technician->user->id,
                        'name' => $request->technician->user->name,
                        'email' => $request->technician->user->email,
                        'phone' => $request->technician->user->phone,
                    ] : null,

                    'vehicle' => $request->vehicle ? [
                        'id' => $request->vehicle->id,
                        'plate_number' => $request->vehicle->plate_number,
                        'vin' => $request->vehicle->vin,
                        'brand' => $request->vehicle->brand,
                        'model' => $request->vehicle->model,
                        'color' => $request->vehicle->color,
                        'year' => $request->vehicle->year,
                    ] : null,

                    'inventory' => $request->inventory ? [
                        'id' => $request->inventory->id,
                        'code_no' => $request->inventory->code_no,
                        'name' => $request->inventory->name,
                        'category' => $request->inventory->category,
                        'specification' => $request->inventory->specification,
                    ] : null,
                ];
            });

        return Inertia::render('owner/maintenance-requests/Index', [
            'requests' => $requests,
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

        $technicians = $franchise->technicians()
            ->with('user')
            ->get()
            ->map(fn($technicians) => [
                'id' => $technicians->id,
                'name' => $technicians->user?->name,
                'email' => $technicians->user?->email,
                'phone' => $technicians->user?->phone,
            ]);

        $inventories = Inventory::all();

        return Inertia::render('owner/maintenance-requests/Create', [
            'vehicles' => $vehicles,
            'technicians' => $technicians,
            'inventories' => $inventories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMaintenanceRequest $request)
    {
        DB::transaction(function () use ($request) {
            $pendingStatusId = Status::where('name', 'active')->firstOrFail()->id;
            $franchise = auth()->user()->ownerDetails?->franchises()->first();
            $franchiseId = $franchise?->id;

            Maintenance::create([
                'vehicle_id' => $request->vehicle,
                'status_id' => $pendingStatusId,
                'technician_id' => $request->technician,
                'inventory_id' => $request->inventory,
                'description' => $request->description,
                'maintenance_date' => $request->maintenance_date,
                'next_maintenance_date' => $request->next_maintenance_date,
            ]);

            Vehicle::where('id', $request->vehicle)->update([
                'status_id' => 5,
            ]);
        });

        return to_route('owner.maintenance-requests.index');
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


    /**
     * Find current franchise
     */
    protected function getFranchiseOrDefault()
    {
        return auth()->user()->ownerDetails?->franchises()->first();
    }
}
