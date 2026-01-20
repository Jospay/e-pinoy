<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDriver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DriverApplicationController extends Controller
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

        // $driversQuery = User::with('driverDetails.status')
        //     ->whereHas('userType', fn($q) => $q->where('name', 'driver'))
        //     ->whereHas('driverDetails', fn ($q) =>
        //         $q->where('is_verified', 1)
        //         ->whereHas('status', fn ($s) =>
        //             $s->whereIn('name', ['pending'])
        //         )
        //     );

        $driversQuery = User::with('driverDetails.status')
            ->whereHas('userType', fn ($q) =>
                $q->where('name', 'driver')
            )
            ->whereHas('driverDetails', fn ($q) =>
                $q->whereHas('status', fn ($s) =>
                    $s->whereIn('name', ['pending', 'inactive'])
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
                'license_number'  => $user->driverDetails?->license_number,
                'code_number'  => $user->driverDetails?->code_number,
                'license_expiry'  => $user->driverDetails?->license_expiry,
                'is_verified'     => $user->driverDetails?->is_verified,
                'shift'           => $user->driverDetails?->shift,
                'hire_date'       => $user->driverDetails?->hire_date,

                'front_license_picture' => $user->driverDetails?->front_license_picture
                    ? asset('storage/driver_documents/' . $user->driverDetails->front_license_picture)
                    : null,

                'back_license_picture' => $user->driverDetails?->back_license_picture
                    ? asset('storage/driver_documents/' . $user->driverDetails->back_license_picture)
                    : null,

                'nbi_clearance' => $user->driverDetails?->nbi_clearance
                    ? asset('storage/driver_documents/' . $user->driverDetails->nbi_clearance)
                    : null,

                'selfie_picture' => $user->driverDetails?->selfie_picture
                    ? asset('storage/driver_documents/' . $user->driverDetails->selfie_picture)
                    : null,
            ],
        ]);

        return Inertia::render('owner/driver-application/Index', [
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
        $ownerFranchises = auth()->user()->ownerDetails->franchises->pluck('id');

        // Get the action from the request ('approve' or 'deny')
        $action = $request->input('action');

        if ($action === 'approve') {
            // Status 1 for active/approved
            $driver->status_id = 1;

            // Attach to owner's franchises if not already attached
            $driver->franchises()->syncWithoutDetaching($ownerFranchises);

            // Generate code if empty
            if (empty($driver->code_number)) {
                $faker = \Faker\Factory::create();
                do {
                    $code = $faker->bothify('??-####');
                } while (UserDriver::where('code_number', $code)->exists());

                $driver->code_number = $code;
            }
            $driver->is_verified = true;

        } elseif ($action === 'deny') {
            // Status 18 for Deny
            $driver->status_id = 18;
            // Detach from franchises when denied
            $driver->franchises()->detach($ownerFranchises);
        } else {
            // Fallback for your original toggle logic if no action is provided
            $driver->status_id = ($driver->status_id === 1) ? 2 : 1;
        }

        $driver->save();

        return back()->with('success', 'Driver application updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
