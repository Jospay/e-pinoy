<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        if (!$franchise) {
            abort(404, 'Franchise not found');
        }

        $vehicles = $franchise->vehicles()
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
                    // Return the full URL for the frontend
                    'or_cr' => $vehicle->or_cr
                        ? asset('storage/vehicle_documents/' . $vehicle->or_cr)
                        : null,
                ];
            });

        return Inertia::render('owner/vehicles/Index', [
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'plate_number' => 'required|string|max:255|unique:vehicles',
            'vin'          => 'required|string|max:255|unique:vehicles',
            'brand'        => 'required|string|max:255',
            'model'        => 'required|string|max:255',
            'color'        => 'required|string|max:255',
            'year'         => 'required|integer',
            'status_id'    => 'required|exists:statuses,id',
            'or_cr'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        if (!$franchise) {
            return redirect()->back()->with('error', 'No franchise found.');
        }

        // 1. Create the record first to get the ID (optional, or use timestamp)
        $vehicle = new Vehicle($request->except('or_cr'));
        $vehicle->franchise_id = $franchise->id;

        // 2. Handle File Upload using your driver logic
        if ($request->hasFile('or_cr')) {
            $file = $request->file('or_cr');
            // Using time() + plate_number to ensure uniqueness before ID is generated
            $filename = time() . '_or_cr_' . str_replace(' ', '_', $request->plate_number) . '.' . $file->getClientOriginalExtension();

            $file->storeAs('vehicle_documents', $filename, 'public');
            $vehicle->or_cr = $filename;
        }

        $vehicle->save();

        return redirect()->back()->with('success', 'Vehicle created!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'plate_number' => 'required|string|max:255|unique:vehicles,plate_number,' . $vehicle->id,
            'vin'          => 'required|string|max:255|unique:vehicles,vin,' . $vehicle->id,
            'brand'        => 'required|string|max:255',
            'model'        => 'required|string|max:255',
            'color'        => 'required|string|max:255',
            'year'         => 'required|integer',
            'status_id'    => 'required|exists:statuses,id',
            'or_cr'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Handle File Update
        if ($request->hasFile('or_cr')) {
            // Delete old file
            if ($vehicle->or_cr) {
                Storage::disk('public')->delete('vehicle_documents/' . $vehicle->or_cr);
            }

            $file = $request->file('or_cr');
            $filename = time() . '_or_cr_' . $vehicle->id . '.' . $file->getClientOriginalExtension();

            $file->storeAs('vehicle_documents', $filename, 'public');
            $vehicle->or_cr = $filename;
        }

        // Update other fields
        $vehicle->update($request->only([
            'plate_number', 'vin', 'brand', 'model', 'color', 'year', 'status_id'
        ]));

        $vehicle->save();

        return redirect()->back()->with('success', 'Vehicle updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        // Clean up the file from storage before deleting the record
        if ($vehicle->or_cr) {
            Storage::disk('public')->delete('vehicle_documents/' . $vehicle->or_cr);
        }

        $vehicle->delete();
        return redirect()->back()->with('success', 'Vehicle deleted!');
    }
}
