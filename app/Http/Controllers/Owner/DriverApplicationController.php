<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Status;
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
    public function index(Request $request)
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();
        if (!$franchise) abort(404, 'Franchise not found');

        // Start Query
        $driversQuery = User::with('driverDetails.status')
            ->whereHas('userType', fn($q) => $q->where('name', 'driver'));

        // FILTER: Focus on status
        $statusFilter = $request->input('status', 'available');

        $driversQuery->whereHas('driverDetails', function ($q) use ($statusFilter) {
            $q->where('is_verified', 1)
              ->whereHas('status', fn($s) => $s->where('name', $statusFilter));
        });

        // Global search
        if ($search = $request->input('search')) {
            $driversQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $drivers = $driversQuery->orderBy('created_at', 'desc')
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
            ]);

        return Inertia::render('owner/driver-application/Index', [
            'drivers' => $drivers,
            'filters' => [
                'search' => $request->search,
                'status' => $statusFilter,
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
