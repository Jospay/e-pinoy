<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Status;
use App\Models\UserDriver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DriverManagementController extends Controller
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

        $statusQuery = ['active', 'inactive', 'suspended', 'retired'];

        $drivers = $branch->drivers()
            ->with(['user', 'status'])
            ->whereHas('status', function ($query) use ($statusQuery) {
                $query->whereIn('name', $statusQuery);
            })
            ->paginate(10)
            ->through(fn($driver) => [
                'id' => $driver->id,
                'name' => $driver->user?->name,
                'username' => $driver->user?->username,
                'email' => $driver->user?->email,
                'phone' => $driver->user?->phone,
                'region' => $driver->user?->region,
                'province' => $driver->user?->province,
                'city' => $driver->user?->city,
                'barangay' => $driver->user?->barangay,
                'address' => $driver->user?->address,
                'status' => $driver->status?->name,

                'details' => [
                    'code_number'  => $driver->code_number,
                    'license_number'  => $driver->license_number,
                    'license_expiry'  => $driver->license_expiry,
                    'is_verified'     => $driver->is_verified,
                    'shift'           => $driver->shift,
                    'hire_date'       => $driver->hire_date,

                    'front_license_picture' => $driver->front_license_picture
                        ? asset('storage/' . $driver->front_license_picture)
                        : null,

                    'back_license_picture' => $driver->back_license_picture
                        ? asset('storage/' . $driver->back_license_picture)
                        : null,

                    'nbi_clearance' => $driver->nbi_clearance
                        ? asset('storage/' . $driver->nbi_clearance)
                        : null,

                    'selfie_picture' => $driver->selfie_picture
                        ? asset('storage/' . $driver->selfie_picture)
                        : null,
                ],
            ]);

        $statuses = Status::whereIn('name', ['active', 'suspended', 'retired', 'inactive'])
            ->get(['id', 'name']);

        return Inertia::render('manager/driver-management/Index', [
            'drivers' => $drivers,
            'statuses' => $statuses,
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
    public function update(Request $request, string $id)
    {
        $branch = auth()->user()->managerDetails?->branches()->first();

        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        $driver = $branch->drivers()->find($id);
        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }

        $request->validate([
            'status_id' => 'required|exists:statuses,id',
        ]);

        $driver->update([
            'status_id' => $request->status_id
        ]);

        return redirect()->back()->with('success', 'Driver status updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $driver = UserDriver::findOrFail($id);

        $ownerBranches = auth()->user()->managerDetails->branches->pluck('id');

        $driver->branches()->detach($ownerBranches);
        $driver->status_id = 6;
        $driver->save();

        return back()->with('success', 'Driver removed from your branch successfully.');
    }
}
