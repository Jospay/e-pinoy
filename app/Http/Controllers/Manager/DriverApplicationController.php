<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDriver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DriverApplicationController extends Controller
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

        $driversQuery = User::with('driverDetails.status')
            ->whereHas('userType', fn($q) => $q->where('name', 'driver'))
            ->whereHas('driverDetails', fn ($q) =>
                $q->where('is_verified', 1)
                ->whereHas('status', fn ($s) =>
                    $s->whereIn('name', ['pending'])
                )
            );

        // Global search
        if ($search = request('search')) {
            $driversQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $drivers = $driversQuery->paginate(10)->through(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'region' => $user->region,
            'province' => $user->province,
            'city' => $user->city,
            'barangay' => $user->barangay,
            'address' => $user->address,
            'status' => $user->driverDetails?->status?->name,

            'details' => [
                'code_number'  => $user->driverDetails?->code_number,
                'license_number'  => $user->driverDetails?->license_number,
                'license_expiry'  => $user->driverDetails?->license_expiry,
                'is_verified'     => $user->driverDetails?->is_verified,
                'shift'           => $user->driverDetails?->shift,
                'hire_date'       => $user->driverDetails?->hire_date,

                'front_license_picture' => $user->driverDetails?->front_license_picture
                    ? asset('storage/' . $user->driverDetails->front_license_picture)
                    : null,

                'back_license_picture' => $user->driverDetails?->back_license_picture
                    ? asset('storage/' . $user->driverDetails->back_license_picture)
                    : null,

                'nbi_clearance' => $user->driverDetails?->nbi_clearance
                    ? asset('storage/' . $user->driverDetails->nbi_clearance)
                    : null,

                'selfie_picture' => $user->driverDetails?->selfie_picture
                    ? asset('storage/' . $user->driverDetails->selfie_picture)
                    : null,
            ],
        ]);

        return Inertia::render('manager/driver-application/Index', [
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
    public function update(Request $request, string $id)
    {
        $driver = UserDriver::findOrFail($id);

        $ownerBranches = auth()->user()->managerDetails->branches->pluck('id');

        $driver->status_id = $driver->status_id === 1 ? 2 : 1;
        $driver->save();

        if ($driver->status_id === 1) {
            $driver->branches()->syncWithoutDetaching($ownerBranches);
        } else {
            $driver->branches()->detach($ownerBranches);
        }

        return back()->with('success', 'Driver status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
