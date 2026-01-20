<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\IdType;
use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\FranchiseResource;
use App\Models\ActionVerification;
use App\Models\Franchise;
use App\Models\Status;
use App\Models\User;
use App\Models\UserOwner;
use App\Notifications\AcceptFranchiseApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FranchiseController extends Controller
{
    public function show(Franchise $franchise)
    {
        $franchise->loadMissing(['status:id,name']);

        return new FranchiseResource($franchise);
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

    public function uploadContract(Request $request, Franchise $franchise)
    {
        $request->validate([
            'contract_attachment' => 'required|file|mimes:pdf,doc,docx,odt,rtf,txt,xls,xlsx|max:10240',
        ]);

        $file = $request->file('contract_attachment');

        if ($franchise->contract_attachment) {
            Storage::delete($franchise->contract_attachment);
        }

        $path = $file->store('franchise_contracts', 'public');

        $franchise->contract_attachment = $path;
        $franchise->save();

        return back();
    }

    public function create()
    {
        return Inertia::render('super-admin/franchise/Create', [
            'idTypes' => IdType::options(),
        ]);
    }

    public function store(Request $request)
    {
        $userTypeId = 2;

        // 1. Validation
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)],
            'name' => ['nullable', 'string', 'max:255'],

            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique(User::class),
                Rule::unique('franchises', 'email'),
            ],

            'phone' => [
                'required', 'string', 'max:20',
                Rule::unique(User::class),
                Rule::unique('franchises', 'phone'),
            ],

            'password' => $this->passwordRules(),

            // Home address
            'home_region' => ['required', 'string', 'max:255'],
            'home_province' => ['nullable', 'string', 'max:255', 'required_unless:home_region,NCR'],
            'home_city' => ['required', 'string', 'max:255'],
            'home_barangay' => ['required', 'string', 'max:255'],
            'home_postal_code' => ['required', 'string', 'max:20'],
            'home_address' => ['required', 'string', 'max:255'],

            // Franchise info
            'franchise_name' => ['required', 'string', 'max:255'],
            'franchise_region' => ['required', 'string', 'max:255'],
            'franchise_province' => ['nullable', 'string', 'max:255', 'required_unless:franchise_region,NCR'],
            'franchise_city' => ['required', 'string', 'max:255'],
            'franchise_barangay' => ['required', 'string', 'max:255'],
            'franchise_postal_code' => ['required', 'string', 'max:20'],
            'franchise_address' => ['required', 'string', 'max:255'],

            // Owner ID
            'valid_id_type' => [
                'required',
                Rule::in([
                    'National ID',
                    'Passport',
                    'Driver License',
                    'Voter ID',
                    'Unified Multi-Purpose ID',
                    'TIN ID',
                ]),
            ],

            'valid_id_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('user_owners', 'valid_id_number'),
            ],

            // Files
            'front_valid_id_picture' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'back_valid_id_picture' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'dti_certificate' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'mayor_permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'proof_capital' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],

            'terms1' => ['required', 'accepted'],
            'terms2' => ['required', 'accepted'],
        ]);

        // 2. Transaction
        $user = DB::transaction(function () use ($validated, $userTypeId) {

            $pendingStatusId = 6;

            // Store files
            $frontIdPath = $validated['front_valid_id_picture']->store('owner_ids', 'public');
            $backIdPath = $validated['back_valid_id_picture']->store('owner_ids', 'public');
            $dtiPath = $validated['dti_certificate']->store('franchise_documents', 'public');
            $mayorPath = $validated['mayor_permit']->store('franchise_documents', 'public');
            $proofPath = $validated['proof_capital']->store('franchise_documents', 'public');

            // Create User
            $user = User::create([
                'username' => $validated['username'],
                'user_type_id' => $userTypeId,
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),

                'address' => $validated['home_address'],
                'region' => $validated['home_region'],
                'province' => $validated['home_province'] ?? null,
                'city' => $validated['home_city'],
                'barangay' => $validated['home_barangay'],
                'postal_code' => $validated['home_postal_code'],
            ]);

            // Create Owner
            $owner = UserOwner::create([
                'id' => $user->id,
                'status_id' => $pendingStatusId,
                'valid_id_type' => $validated['valid_id_type'],
                'valid_id_number' => $validated['valid_id_number'],
                'front_valid_id_picture' => $frontIdPath,
                'back_valid_id_picture' => $backIdPath,
            ]);

            // Create Franchise
            Franchise::create([
                'owner_id' => $owner->id,
                'status_id' => $pendingStatusId,
                'name' => $validated['franchise_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],

                'address' => $validated['franchise_address'],
                'region' => $validated['franchise_region'],
                'province' => $validated['franchise_province'] ?? null,
                'city' => $validated['franchise_city'],
                'barangay' => $validated['franchise_barangay'],
                'postal_code' => $validated['franchise_postal_code'],

                'dti_registration_attachment' => $dtiPath,
                'mayor_permit_attachment' => $mayorPath,
                'proof_agreement_attachment' => $proofPath,
            ]);

            return $user;
        });

        return redirect()
            ->route('super-admin.dashboard')
            ->with('success', 'Franchise created successfully');
    }

    public function edit(Franchise $franchise)
    {
        $franchise->load('owner.user');

        return Inertia::render('super-admin/franchise/Edit', [
            'franchise' => [
                ...$franchise->only([
                    'id',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'region',
                    'province',
                    'city',
                    'barangay',
                    'postal_code',
                    'dti_registration_attachment',
                    'mayor_permit_attachment',
                    'proof_agreement_attachment',
                ]),
                'owner' => $franchise->owner ? [
                    'id' => $franchise->owner->id,
                    'user' => $franchise->owner->user ? $franchise->owner->user->only([
                        'id',
                        'name',
                        'email',
                        'phone',
                    ]) : null,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request, Franchise $franchise)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('franchises')->ignore($franchise->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('franchises')->ignore($franchise->id)],
            'region' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255', 'required_unless:region,NCR'],
            'city' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'dti_registration_attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'mayor_permit_attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'proof_agreement_attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
        ]);

        $updateData = $validated;

        foreach (['dti_registration_attachment', 'mayor_permit_attachment', 'proof_agreement_attachment'] as $field) {
            if ($request->hasFile($field)) {
                // Delete old file if exists
                if ($franchise->$field) {
                    Storage::delete($franchise->$field);
                }

                $updateData[$field] = $request->file($field)->store('franchise_documents', 'public');
            } else {
                unset($updateData[$field]);
            }
        }

        $franchise->update($updateData);

        return redirect()
            ->route('super-admin.dashboard')
            ->with('success', 'Franchise updated successfully');
    }

    public function destroy(Request $request, string $id)
    {
        // $request->validate([
        //     'code' => 'required|digits:6',
        // ]);

        // $verification = ActionVerification::where([
        //     'user_id' => $request->user()->id,
        //     'action' => 'delete_franchise',
        //     'code' => $request->code,
        // ])->where('expires_at', '>', now())->first();

        // if (! $verification) {
        //     return response()->json(['message' => 'Invalid or expired verification code.'], 422);
        // }

        DB::transaction(function () use ($id) {
            $franchise = Franchise::with('owner.user')->findOrFail($id);

            // $owner = $franchise->owner;
            // $user = $owner?->user;

            // // Delete children first
            // if ($user) $user->delete();
            // if ($owner) $owner->delete();

            // Delete franchise
            $franchise->delete();
        });

        // Delete verification after successful deletion
        // $verification->delete();

        // return redirect()->route('super-admin.dashboard');
        return back();
    }

    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/[a-z]/',      // lowercase
            'regex:/[A-Z]/',      // uppercase
            'regex:/[0-9]/',      // number
        ];
    }
}
