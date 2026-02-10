<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Status;
use App\Models\User;
use App\Models\UserDriver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DriverApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Get the Franchise context
        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        if (!$franchise) abort(404, 'Franchise not found');

        // 2. Fetch the ID for 'active' status dynamically
        $activeStatusId = Status::where('name', 'active')->value('id');

        // 3. Prepare Filters and Reference Data
        $franchiseVehicleTypes = $franchise->vehicleTypes()
            ->wherePivot('status_id', $activeStatusId)
            ->get(['vehicle_types.id', 'name']);

        // --- SECURITY FIX: URL Injection Protection ---
        $allowedTypeNames = $franchiseVehicleTypes->pluck('name')->toArray();
        $requestedType = $request->input('vehicle_type');

        // If the requested type isn't in the franchise's active types, force the default
        if (!$requestedType || !in_array($requestedType, $allowedTypeNames)) {
            $activeVehicleType = $franchiseVehicleTypes->first()?->name;
        } else {
            $activeVehicleType = $requestedType;
        }
        // ----------------------------------------------

        $statusFilter = $request->input('status', 'available');
        $search = $request->input('search');

        // 4. Build the Driver Query
        $driversQuery = User::with(['driverDetails.status', 'driverDetails.vehicleTypes'])
            ->whereHas('userType', fn($q) => $q->where('name', 'driver'));

        $driversQuery->whereHas('driverDetails', function ($q) use ($statusFilter, $franchise, $activeVehicleType) {
            $q->where('is_verified', 1)
            ->whereHas('status', fn($s) => $s->where('name', $statusFilter));

            // Filter drivers specifically by the validated vehicle type name
            if ($activeVehicleType) {
                $q->whereHas('vehicleTypes', function($vt) use ($activeVehicleType) {
                    $vt->where('name', $activeVehicleType);
                });
            }

            // Logic for franchise-specific approval
            if ($statusFilter === 'for approval') {
                $q->whereHas('franchises', function ($f) use ($franchise) {
                    $f->where('franchises.id', $franchise->id);
                });
            }
        });

        // 5. Handle Search
        if ($search) {
            $driversQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return Inertia::render('owner/driver-application/Index', [
            'drivers' => $driversQuery->orderBy('created_at', 'desc')
                ->paginate(10)
                ->withQueryString()
                ->through(fn($user) => [
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
                    'vehicle_types' => $user->driverDetails?->vehicleTypes->map(fn($type) => [
                        'id' => $type->id,
                        'name' => $type->name
                    ]) ?? [],
                    'details' => [
                        'license_number' => $user->driverDetails?->license_number,
                        'code_number' => $user->driverDetails?->code_number,
                        'license_expiry' => $user->driverDetails?->license_expiry,
                        'is_verified' => $user->driverDetails?->is_verified,
                        'shift' => $user->driverDetails?->shift,
                        'hire_date' => $user->driverDetails?->hire_date,
                        'front_license_picture' => $user->driverDetails?->front_license_picture
                            ? asset('storage/driver_documents/' . $user->driverDetails->front_license_picture) : null,
                        'back_license_picture' => $user->driverDetails?->back_license_picture
                            ? asset('storage/driver_documents/' . $user->driverDetails->back_license_picture) : null,
                        'nbi_clearance' => $user->driverDetails?->nbi_clearance
                            ? asset('storage/driver_documents/' . $user->driverDetails->nbi_clearance) : null,
                        'selfie_picture' => $user->driverDetails?->selfie_picture
                            ? asset('storage/driver_documents/' . $user->driverDetails->selfie_picture) : null,
                    ],
                ]),
            'franchiseVehicleTypes' => $franchiseVehicleTypes,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
                'vehicle_type' => $activeVehicleType // Returns the validated type to the frontend
            ]
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Use id to find the driver profile
        $driverProfile = UserDriver::where('id', $id)->firstOrFail();
        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        if (!$franchise) {
            return back()->withErrors(['error' => 'No franchise found for this account.']);
        }

        $action = $request->input('action');

        if ($action === 'request') {
            $franchise->drivers()->syncWithoutDetaching($driverProfile->id);

            $status = Status::where('name', 'for approval')->first();
            if ($status) {
                $driverProfile->status_id = $status->id;
            }

            if (empty($driverProfile->code_number)) {
                $faker = \Faker\Factory::create();
                do {
                    $code = $faker->bothify('DRV-####');
                } while (UserDriver::where('code_number', $code)->exists());

                $driverProfile->code_number = $code;
            }

            $driverProfile->save();
            return back()->with('success', 'Request sent to driver successfully.');
        }

        // NEW: Handle Cancel Action
        if ($action === 'cancel') {
            $franchise->drivers()->detach($driverProfile->id);

            $status = Status::where('name', 'available')->first();
            if ($status) {
                $driverProfile->status_id = $status->id;
            }

            $driverProfile->save();
            return back()->with('success', 'Request cancelled successfully.');
        }

        return back()->withErrors(['error' => 'Invalid action.']);
    }
}
