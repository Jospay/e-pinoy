<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\UserOwner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enums\IdType;

class FranchiseOwnerController extends Controller
{
    public function edit(UserOwner $userOwner)
    {
        $userOwner->load(['user', 'franchises', 'status']);
        
        return Inertia::render('super-admin/franchise/OwnerEdit', [
            'owner' => [
                'id' => $userOwner->id,
                'valid_id_type' => $userOwner->valid_id_type,
                'valid_id_number' => $userOwner->valid_id_number,
                'front_valid_id_picture' => $userOwner->front_valid_id_picture,
                'back_valid_id_picture' => $userOwner->back_valid_id_picture,
                'status' => $userOwner->status ? $userOwner->status->only(['id', 'name']) : null,
                'user' => $userOwner->user ? $userOwner->user->only([
                    'id',
                    'username',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'region',
                    'province',
                    'city',
                    'barangay',
                    'postal_code',
                ]) : null,
            ],
            'franchises' => $userOwner->franchises->map(function ($franchise) {
                return $franchise->only([
                    'id',
                    'name',
                    'email',
                ]);
            }),
            'idTypes' => IdType::options(),
        ]);
    }
    
    public function update(Request $request, UserOwner $userOwner)
    {
        $validated = $request->validate([
            // User fields
            'username' => 'required|string|max:255|unique:users,username,' . $userOwner->user->id,
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $userOwner->user->id,
            'phone' => 'required|string|max:20',
            
            // Address fields
            'region' => 'required|string|max:255',
            'province' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'address' => 'required|string|max:500',
            
            // Owner specific fields
            'valid_id_type' => 'required|string|max:255',
            'valid_id_number' => 'required|string|max:255',
            'front_valid_id_picture' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'back_valid_id_picture' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Update user information
        $userOwner->user->update([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'region' => $validated['region'],
            'province' => $validated['province'] ?? null,
            'city' => $validated['city'],
            'barangay' => $validated['barangay'],
            'postal_code' => $validated['postal_code'],
            'address' => $validated['address'],
        ]);

        // Prepare owner data
        $ownerData = [
            'valid_id_type' => $validated['valid_id_type'],
            'valid_id_number' => $validated['valid_id_number'],
        ];

        // Handle file uploads
        if ($request->hasFile('front_valid_id_picture')) {
            // Delete old file if exists
            if ($userOwner->front_valid_id_picture) {
                \Storage::disk('public')->delete($userOwner->front_valid_id_picture);
            }
            $ownerData['front_valid_id_picture'] = $request->file('front_valid_id_picture')
                ->store('valid-ids', 'public');
        }

        if ($request->hasFile('back_valid_id_picture')) {
            // Delete old file if exists
            if ($userOwner->back_valid_id_picture) {
                \Storage::disk('public')->delete($userOwner->back_valid_id_picture);
            }
            $ownerData['back_valid_id_picture'] = $request->file('back_valid_id_picture')
                ->store('valid-ids', 'public');
        }

        // Update owner information
        $userOwner->update($ownerData);

        return redirect()
            ->route('super-admin.dashboard')
            ->with('success', 'Owner updated successfully');
    }
}