<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Status;
use App\Models\UserDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DriverManagementController extends Controller
{
    public function index()
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        if (!$franchise) {
            abort(404, 'Franchise not found');
        }

        $statusQuery = ['active', 'inactive', 'suspended', 'retired'];

        $drivers = $franchise->drivers()
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
                    'code_number'     => $driver->code_number,
                    'license_number'  => $driver->license_number,
                    'license_expiry'  => $driver->license_expiry,
                    'is_verified'     => $driver->is_verified,
                    'shift'           => $driver->shift,
                    'hire_date'       => $driver->hire_date,

                    'front_license_picture' => $driver->front_license_picture
                        ? asset('storage/driver_documents/' . $driver->front_license_picture)
                        : null,

                    'back_license_picture' => $driver->back_license_picture
                        ? asset('storage/driver_documents/' . $driver->back_license_picture)
                        : null,

                    'nbi_clearance' => $driver->nbi_clearance
                        ? asset('storage/driver_documents/' . $driver->nbi_clearance)
                        : null,

                    'selfie_picture' => $driver->selfie_picture
                        ? asset('storage/driver_documents/' . $driver->selfie_picture)
                        : null,
                ],
            ]);

        $statuses = Status::whereIn('name', ['active', 'suspended', 'retired', 'inactive'])
            ->get(['id', 'name']);

        return Inertia::render('owner/driver-management/Index', [
            'drivers' => $drivers,
            'statuses' => $statuses,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $franchise = auth()->user()->ownerDetails?->franchises()->first();

        if (!$franchise) {
            return redirect()->back()->withErrors(['message' => 'Franchise not found']);
        }

        // We use $id because in your DB, Driver ID and User ID are identical.
        $driver = UserDriver::with('user')->findOrFail($id);

        // Security check
        if (!$driver->franchises()->where('franchise_id', $franchise->id)->exists()) {
            abort(403, 'Unauthorized action.');
        }

        // 1. Handle Document File Updates
        $fileFields = ['front_license_picture', 'back_license_picture', 'nbi_clearance', 'selfie_picture'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                if ($driver->$field) {
                    Storage::disk('public')->delete('driver_documents/' . $driver->$field);
                }
                $file = $request->file($field);
                $filename = time() . '_' . $field . '_' . $driver->id . '.' . $file->getClientOriginalExtension();
                $file->storeAs('driver_documents', $filename, 'public');
                $driver->$field = $filename;
                $driver->save();
                return redirect()->back()->with('success', 'Document updated!');
            }
        }

        // 2. Handle Profile & Unique Field Updates
        if ($request->hasAny(['email', 'phone', 'license_number', 'code_number', 'region'])) {

            // KEY CHANGE: Since you use Shared Primary Keys, $driver->id IS the User's ID.
            $userId = $driver->id;

            $request->validate([
                'email' => [
                    'sometimes',
                    'email',
                    Rule::unique('users', 'email')->ignore($userId),
                ],
                'phone' => [
                    'sometimes',
                    Rule::unique('users', 'phone')->ignore($userId),
                ],
                'code_number' => [
                    'sometimes',
                    Rule::unique('user_drivers', 'code_number')->ignore($driver->id),
                ],
                'license_number' => [
                    'sometimes',
                    Rule::unique('user_drivers', 'license_number')->ignore($driver->id),
                ],
                'license_expiry' => 'sometimes|date|nullable',
                'region'         => 'sometimes|string',
                'province'       => 'sometimes|string|nullable',
                'city'           => 'sometimes|string',
                'barangay'       => 'sometimes|string',
                'shift'          => 'sometimes|string|nullable',
            ]);

            // Update User record
            if ($driver->user) {
                $driver->user->update($request->only([
                    'email', 'phone', 'region', 'province', 'city', 'barangay'
                ]));
            }

            // Update Driver record
            $driver->update($request->only([
                'license_number', 'license_expiry', 'code_number', 'shift'
            ]));

            return redirect()->back()->with('success', 'Driver profile updated!');
        }

        // 3. Handle Status Update
        if ($request->has('status_id')) {
            $request->validate(['status_id' => 'required|exists:statuses,id']);
            $driver->update(['status_id' => $request->status_id]);
            return redirect()->back()->with('success', 'Driver status updated!');
        }

        return redirect()->back();
    }

    public function destroy(string $id)
    {
        $driver = UserDriver::findOrFail($id);
        $ownerFranchises = auth()->user()->ownerDetails->franchises->pluck('id');
        $driver->franchises()->detach($ownerFranchises);
        $driver->status_id = 6;
        $driver->is_verified = false;
        $driver->save();

        return back()->with('success', 'Driver removed successfully.');
    }
}
