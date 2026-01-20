<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleController extends Controller
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

        $vehicles = $branch->vehicles()
            ->with('status')
            ->orderByDesc('created_at')
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
                    'status_name' => $vehicle->status?->name,
                ];
            });

        return Inertia::render('manager/vehicles/Index', [
            'vehicles' => $vehicles,
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
        $data = $request->validate([
            'plate_number' => 'required|string|max:255',
            'vin' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'color' => 'required|string|max:255',
            'year' => 'required|integer',
            'status_id' => 'required|exists:statuses,id',
        ]);

        $branch = auth()->user()->managerDetails?->branches()->first();

        if (!$branch) {
            return redirect()->back()->with('error', 'You do not have an assigned branch.');
        }

        $data['branch_id'] = $branch->id;

        Vehicle::create($data);

        return redirect()->back()->with('success', 'Vehicle created!');
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
    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'plate_number' => 'required|string|max:255',
            'vin' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'color' => 'required|string|max:255',
            'year' => 'required|integer',
            'status_id' => 'required|exists:statuses,id',
        ]);

        $vehicle->update($data);

        return redirect()->back()->with('success', 'Vehicle updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->back()->with('success', 'Vehicle deleted!');
    }
}
