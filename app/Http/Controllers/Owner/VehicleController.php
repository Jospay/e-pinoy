<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;
use App\Models\Status;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        if (!$franchise) {
            abort(404, 'Franchise not found');
        }

        // 1. Get vehicle types first to establish the default filter
        $franchiseVehicleTypes = VehicleType::whereHas('franchises', function($q) use ($franchise) {
            $q->where('franchise_id', $franchise->id);
        })->get();

        // 2. Set the default vehicle type if none is provided in the request
        $selectedType = $request->vehicle_type ?: $franchiseVehicleTypes->first()?->name;

        $vehicles = $franchise->vehicles()
            ->with(['status', 'branch', 'vehicleType'])
            // Filter: Search
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('plate_number', 'like', "%{$search}%")
                    ->orWhere('vin', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
                });
            })
            // Filter: Status
            ->when($request->status, function ($query, $status) {
                if ($status !== 'all') {
                    $query->whereHas('status', fn($q) => $q->where('name', $status));
                }
            })
            // Filter: Branch
            ->when($request->branch_id, function ($query, $branchId) {
                if ($branchId === 'franchise') {
                    $query->whereNull('branch_id');
                } elseif ($branchId === 'only_branches') {
                    $query->whereNotNull('branch_id');
                } elseif ($branchId !== 'all') {
                    $query->where('branch_id', $branchId);
                }
            })
            // Filter: Vehicle Type (Always applied)
            ->when($selectedType, function ($query, $type) {
                $query->whereHas('vehicleType', fn($q) => $q->where('name', $type));
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(function ($vehicle) {
                $orCrValue = $vehicle->or_cr;
                if ($orCrValue && !filter_var($orCrValue, FILTER_VALIDATE_URL)) {
                    $orCrValue = asset('storage/vehicle_documents/' . $orCrValue);
                }

                return [
                    'id'                => $vehicle->id,
                    'plate_number'      => $vehicle->plate_number,
                    'vin'               => $vehicle->vin,
                    'brand'             => $vehicle->brand,
                    'model'             => $vehicle->model,
                    'color'             => $vehicle->color,
                    'year'              => $vehicle->year,
                    'capacity'          => $vehicle->capacity,
                    'status_id'         => $vehicle->status_id,
                    'status_name'       => $vehicle->status?->name,
                    'branch_id'         => $vehicle->branch_id,
                    'branch_name'       => $vehicle->branch?->name,
                    'vehicle_type_id'   => $vehicle->vehicle_type_id,
                    'vehicle_type_name' => $vehicle->vehicleType?->name,
                    'or_cr'             => $orCrValue,
                ];
            });

        $inventories = Inventory::where('franchise_id', $franchise->id)
        ->where('quantity', '>', 0)
        ->get();

        return Inertia::render('owner/vehicles/Index', [
            'vehicles'              => $vehicles,
            'inventories'           => $inventories,
            'branches'              => $franchise->branches,
            'statuses'              => Status::whereIn('name', ['Available', 'Maintenance'])->get(),
            'franchiseVehicleTypes' => $franchiseVehicleTypes,
            'filters'               => [
                'search'       => $request->search,
                'status'       => $request->status ?? 'all',
                'branch_id'    => $request->branch_id ?? 'all',
                'vehicle_type' => $selectedType,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plate_number'    => 'required|string|max:255|unique:vehicles',
            'vin'             => 'required|string|max:255|unique:vehicles',
            'brand'           => 'required|string|max:255',
            'model'           => 'required|string|max:255',
            'color'           => 'required|string|max:255',
            'year'            => 'required|integer',
            'capacity'        => 'required|integer|min:1',
            'status_id'       => 'required|exists:statuses,id',
            'branch_id'       => 'nullable|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'or_cr'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        $vehicle = new Vehicle($validated);
        $vehicle->franchise_id = $franchise->id;

        if ($request->hasFile('or_cr')) {
            $file = $request->file('or_cr');
            $filename = time() . '_' . $request->plate_number . '.' . $file->getClientOriginalExtension();
            $file->storeAs('vehicle_documents', $filename, 'public');
            $vehicle->or_cr = $filename;
        }

        $vehicle->save();

        return redirect()->back()->with('success', 'Vehicle created!');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'plate_number'    => 'required|string|max:255|unique:vehicles,plate_number,' . $vehicle->id,
            'vin'             => 'required|string|max:255|unique:vehicles,vin,' . $vehicle->id,
            'brand'           => 'required|string|max:255',
            'model'           => 'required|string|max:255',
            'color'           => 'required|string|max:255',
            'year'            => 'required|integer',
            'capacity'        => 'required|integer|min:1',
            'status_id'       => 'required|exists:statuses,id',
            'branch_id'       => 'nullable|exists:branches,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'or_cr'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($request->hasFile('or_cr')) {
            // Delete old file if exists
            if ($vehicle->or_cr) {
                Storage::disk('public')->delete('vehicle_documents/' . $vehicle->or_cr);
            }

            $file = $request->file('or_cr');
            $filename = time() . '_' . $vehicle->id . '.' . $file->getClientOriginalExtension();
            $file->storeAs('vehicle_documents', $filename, 'public');

            // Manual assignment for the file path
            $vehicle->or_cr = $filename;
        }

        // Update other fields from validated data
        $vehicle->fill($validated);
        $vehicle->save();

        return redirect()->back()->with('success', 'Vehicle updated!');
    }

    public function storeMaintenance(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'inventory_id' => 'required|exists:inventories,id',
            'quantity' => 'required|integer|min:1',
            'maintenance_date' => 'required|date',
            'next_maintenance_date' => 'required|date|after_or_equal:maintenance_date',
            'description' => 'required|string|min:5',
        ]);

        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        if (!$franchise) {
            return redirect()->back()->with('error', 'Franchise context not found.');
        }

        $inventory = Inventory::where('id', $request->inventory_id)
                        ->where('franchise_id', $franchise->id)
                        ->firstOrFail();

        // BETTER VALIDATION: Throw a validation exception for 'quantity'
        if ($inventory->quantity < $request->quantity) {
            throw ValidationException::withMessages([
                'quantity' => ["Insufficient stock. Only {$inventory->quantity} units available."],
            ]);
        }

        return \DB::transaction(function () use ($request, $inventory, $franchise) {
            $vehicle = Vehicle::findOrFail($request->vehicle_id);

            $maintenance = Maintenance::create([
                'vehicle_id'            => $request->vehicle_id,
                'inventory_id'          => $request->inventory_id,
                'quantity'              => $request->quantity,
                'maintenance_date'      => $request->maintenance_date,
                'next_maintenance_date' => $request->next_maintenance_date,
                'description'           => $request->description,
            ]);

            Expense::create([
                'franchise_id'   => $franchise->id,
                'branch_id'      => $vehicle->branch_id,
                'maintenance_id' => $maintenance->id,
                'invoice_no'     => 'INV-' . strtoupper(uniqid()),
                'amount'         => $inventory->unit_price * $request->quantity,
                'currency'       => 'PHP',
                'notes'          => "Maintenance for {$vehicle->plate_number}: {$request->description}",
                'payment_date'   => now(),
            ]);

            $vehicle->update(['status_id' => 5]);
            $inventory->subtractStock($request->quantity);

            return redirect()->back()->with('success', 'Maintenance record logged!');
        });
    }

    /**
     * Get maintenance history for the vehicle.
     */
    public function maintenanceHistory(Vehicle $vehicle)
    {
        // Security check: Ensure owner owns this vehicle
        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        if ($vehicle->franchise_id !== $franchise?->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $history = $vehicle->maintenances()
            ->with('inventory')
            ->orderByDesc('maintenance_date')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'inventory_name' => $item->inventory?->name ?? 'General Maintenance',
                    'category' => $item->inventory?->category ?? 'Other',
                    'maintenance_date' => $item->maintenance_date->format('M d, Y'),
                    'next_maintenance_date' => $item->next_maintenance_date
                        ? $item->next_maintenance_date->format('M d, Y')
                        : 'N/A',
                    'specification' => $item->inventory?->specification ?? 'N/A',
                    'quantity' => $item->quantity,
                    'description' => $item->description,
                ];
            });

        return response()->json($history);
    }
}
