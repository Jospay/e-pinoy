<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\IdType;
use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\FranchiseResource;
use App\Http\Requests\SuperAdmin\StoreFranchiseRequest;
use App\Models\UserOwner;
use App\Models\UserType;
use App\Models\Franchise;
use App\Models\VehicleType;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Notifications\AcceptFranchiseApplication;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

use Inertia\Inertia;

class FranchiseController extends Controller
{
    public function show(Franchise $franchise)
    {
        $franchise->loadMissing(['status:id,name']);

        return new FranchiseResource($franchise);
    }

    public function create(): Response
    {
        // $vehicleTypes = VehicleType::all()->map(function ($type) {
        //     return [
        //         'value' => (string) $type->id,
        //         'label' => $type->name,
        //     ];
        // });

        return Inertia::render('super-admin/dashboard/FranchiseCreate', [
            'idTypeOptions' => IdType::options(),
            // 'vehicleTypes' => $vehicleTypes,
        ]);
    }

    public function store(StoreFranchiseRequest $request)
    {
        DB::transaction(function () use ($request) {
            // 1. Get IDs for Status and UserType
            $activeStatusId = Status::where('name', 'active')->firstOrFail()->id;
            $userTypeId = UserType::where('name', 'owner')->firstOrFail()->id;
            
            // 2a. Store all files
            $frontIdPath = $request['front_valid_id_picture']->store('owner_ids', 'public');
            $backIdPath = $request['back_valid_id_picture']->store('owner_ids', 'public');
            $dtiPath = $request['dti_certificate']->store('franchise_documents', 'public');
            $mayorPermitPath = $request['mayor_permit']->store('franchise_documents', 'public');
            $proofAgreementPath = $request['proof_capital']->store('franchise_documents', 'public');

            // 2b. Create User
            $newUser = User::create([
                'username' => $request['username'],
                'user_type_id' => $userTypeId,
                'name' => empty($request['name']) ? null : $request['name'],
                'email' => $request['email'],
                'phone' => $request['phone'],
                'password' => Hash::make($request['password']),
                'address' => $request['home_address'],
                'region' => $request['home_region'],
                'province' => $request['home_province'] ?? null,
                'city' => $request['home_city'],
                'barangay' => $request['home_barangay'],
                'postal_code' => $request['home_postal_code'],
            ]);

            // 2c. Create UserOwner
            $userOwner = UserOwner::create([
                'id' => $newUser->id, // Use the new user's ID
                'status_id' => $activeStatusId,
                'valid_id_type' => $request['valid_id_type'],
                'valid_id_number' => $request['valid_id_number'],
                'front_valid_id_picture' => $frontIdPath,
                'back_valid_id_picture' => $backIdPath,
            ]);

            // 2d. Create Franchise
            Franchise::create([
                'owner_id' => $userOwner->id, // Use the ID from the created owner
                'status_id' => $activeStatusId,
                'name' => $request['franchise_name'],
                'email' => $request['email'], // Same as user
                'phone' => $request['phone'], // Same as user
                'address' => $request['franchise_address'],
                'region' => $request['franchise_region'],
                'province' => $request['franchise_province'] ?? null,
                'city' => $request['franchise_city'],
                'barangay' => $request['franchise_barangay'],
                'postal_code' => $request['franchise_postal_code'],
                'dti_registration_attachment' => $dtiPath,
                'mayor_permit_attachment' => $mayorPermitPath,
                'proof_agreement_attachment' => $proofAgreementPath,
            ]);
        });

        return redirect(route('super-admin.dashboard.index'));
    }

    public function accept(Franchise $franchise)
    {
        $activeStatus = Status::where('name', 'active')->firstOrFail();

        DB::transaction(function () use ($franchise, $activeStatus) {
            // Update franchise status
            $franchise->status_id = $activeStatus->id;
            $franchise->save();

            // Update the owner's status
            $franchise->owner->status_id = $activeStatus->id;
            $franchise->owner->save();

            $franchise->owner->user->notify(new AcceptFranchiseApplication);
        });

        return back();
    }
}
