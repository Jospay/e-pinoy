<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleDriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branch = auth()->user()->managerDetails?->branches()->first();

        if (!$branch) {
            abort(404, 'Branch not found');
        }

        // Vehicles in this branch
        $vehicles = $branch->vehicles()
            ->with(['driver.user', 'status'])
            ->paginate(10)
            ->through(function ($vehicle) {
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
                    'driver' => $vehicle->driver?->user ? [
                        'id' => $vehicle->driver->user->id,
                        'name' => $vehicle->driver->user->name,
                        'email' => $vehicle->driver->user->email,
                        'phone' => $vehicle->driver->user->phone,
                    ] : null,
                ];
            });

        // Drivers in this branch who are NOT assigned to any vehicle
        $drivers = $branch->drivers()
            ->whereDoesntHave('vehicles')
            ->with('user')
            ->get()
            ->map(fn($driver) => [
                'id' => $driver->id,
                'name' => $driver->user?->name,
                'email' => $driver->user?->email,
                'phone' => $driver->user?->phone,
            ]);

        return Inertia::render('manager/vehicle-drivers/Index', [
            'vehicles' => $vehicles,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $driver_id = $request->input('driver_id');
        $status_id = $driver_id ? 1 : 6;

        $vehicle->update([
            'driver_id' => $driver_id,
            'status_id' => $status_id,
        ]);

        return redirect()->back()->with('success', 'Driver assignment updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
