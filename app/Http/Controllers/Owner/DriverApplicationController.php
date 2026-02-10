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
    public function index(Request $request)
{
    $franchise = auth()->user()->ownerDetails?->franchises()->first();
    if (!$franchise) abort(404, 'Franchise not found');

    $activeStatusId = Status::where('name', 'active')->value('id');

    $franchiseVehicleTypes = $franchise->vehicleTypes()
        ->wherePivot('status_id', $activeStatusId)
        ->get(['vehicle_types.id', 'name']);

    $allowedTypeNames = $franchiseVehicleTypes->pluck('name')->toArray();
    $requestedType = $request->input('vehicle_type');

    $activeVehicleType = in_array($requestedType, $allowedTypeNames)
        ? $requestedType
        : $franchiseVehicleTypes->first()?->name;

    $statusFilter = $request->input('status', 'available');
    $search = $request->input('search');

    // Load branches once
    $branches = $franchise->branches()->select('id', 'name')->get();
    $branchIds = $branches->pluck('id');

    $driversQuery = User::with(['driverDetails.status', 'driverDetails.vehicleTypes'])
        ->whereHas('userType', fn($q) => $q->where('name', 'driver'))
        ->whereHas('driverDetails', function ($q) use (
            $statusFilter,
            $franchise,
            $activeVehicleType,
            $branchIds
        ) {
            $q->where('is_verified', 1)
              ->whereHas('status', fn($s) => $s->where('name', $statusFilter));

            if ($activeVehicleType) {
                $q->whereHas('vehicleTypes', fn($vt) =>
                    $vt->where('name', $activeVehicleType)
                );
            }

            if ($statusFilter === 'for approval') {
                $q->where(function ($sub) use ($franchise, $branchIds) {
                    $sub->whereHas('franchises', fn($f) =>
                        $f->where('franchises.id', $franchise->id)
                    )->orWhereHas('branches', fn($b) =>
                        $b->whereIn('branches.id', $branchIds)
                    );
                });
            }
        });

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
                'assignment' => [
                    'type' => $user->driverDetails?->branches->first()?->name ? 'branch' : 'franchise',
                    'name' => $user->driverDetails?->branches->first()?->name
                        ?? $user->driverDetails?->franchises->first()?->name
                        ?? 'Franchise',
                ],
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
            ]),
        'franchiseVehicleTypes' => $franchiseVehicleTypes,
        'branches' => $branches,
        'filters' => [
            'search' => $search,
            'status' => $statusFilter,
            'vehicle_type' => $activeVehicleType
        ]
    ]);
}


    public function update(Request $request, string $id)
    {
        $driverProfile = UserDriver::where('id', $id)->firstOrFail();
        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        if (!$franchise) {
            return back()->withErrors(['error' => 'No franchise found for this account.']);
        }

        $action = $request->input('action');

        // ADDED
        $target = $request->input('target', 'franchise');

        if ($action === 'request') {

            // ADDED: assign to franchise or branch
            if ($target === 'franchise') {
                $franchise->drivers()->syncWithoutDetaching($driverProfile->id);
            } else {
                $branch = $franchise->branches()
                    ->where('id', $target)
                    ->first();

                if ($branch) {
                    $branch->drivers()->syncWithoutDetaching($driverProfile->id);
                }
            }

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

        if ($action === 'cancel') {
            $franchise->drivers()->detach($driverProfile->id);

            // 🔵 ADDED: also detach from all branches
            foreach ($franchise->branches as $branch) {
                $branch->drivers()->detach($driverProfile->id);
            }

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
