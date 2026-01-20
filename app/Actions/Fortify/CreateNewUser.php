<?php

namespace App\Actions\Fortify;

use App\Enums\AccesibilityOption;
use App\Enums\IdType;
use App\Models\User;
use App\Models\UserDriver;
use App\Models\UserPassenger;
use App\Models\UserTechnician;
use App\Models\Franchise;
use App\Models\UserOwner;
use App\Models\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Shifts;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\Expertise;
use Laravel\Fortify\Contracts\CreatesNewUsers;


class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input)
    {
        try {
            $userTypeId = Crypt::decryptString($input['user_type_id']);
        } catch (\Exception $e) {
            abort(403, 'Invalid user type.');
        }

        $userType = UserType::findOrFail($userTypeId);

        switch ($userType->name) {
            case 'driver':
                return $this->createDriver($input, $userTypeId);
            case 'passenger':
                return $this->createPassenger($input, $userTypeId);
            case 'technician':
                return $this->createTechnician($input, $userTypeId);
            case 'owner':
                return $this->createOwner($input, $userTypeId);
            default:
                return $this->createDefault($input, $userTypeId);
        }
    }

    protected function createDriver(array $input, int $userTypeId): User
    {
        // 1. Validation
        Validator::make($input, [
           'username' => ['required', 'string', 'max:255', Rule::unique(User::class)],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique(User::class),
                Rule::unique('franchises', 'email')
            ],
            'phone' => [
                'required', 'string', 'max:20',
                Rule::unique(User::class),
                Rule::unique('franchises', 'phone')
            ],
            'password' => $this->passwordRules(),
            'gender' => ['required', new Enum(Gender::class)],
            'birth_date' => ['required','date','before_or_equal:' . now()->subYears(10)->toDateString(),'after_or_equal:' . now()->subYears(100)->toDateString(),],         
            'address' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255', 'required_unless:region,NCR'],
            'city' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'license_number' => ['required', 'string', 'max:20'],
            'license_expiry' => ['required', 'date','after_or_equal:' . now()->toDateString()],
            'shift' => ['required', new Enum(Shifts::class)],
            'front_license_picture' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'back_license_picture' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'nbi_clearance' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'selfie_picture' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'terms1' => ['required', 'accepted'],
            'terms2' => ['required', 'accepted'],
        ])->validate();

        Validator::make(
            ['user_type_id' => $userTypeId],
            ['user_type_id' => ['required', 'integer', Rule::exists('user_types', 'id')]]
        )->validate();

        // 2. Create Records in a Transaction
        $user = DB::transaction(function () use ($input, $userTypeId) {

            // 'In-Active' status ID is 2, default for drivers
            $inActiveStatusId = 2;

            // 2a. Store all files
            $frontIdPath = $input['front_license_picture']->store('driver_ids', 'public');
            $backIdPath = $input['back_license_picture']->store('driver_ids', 'public');
            $nbiClearancePath = $input['nbi_clearance']->store('driver_documents', 'public');
            $selfiePicturePath = $input['selfie_picture']->store('driver_documents', 'public');

            // 2b. Create User
            $newUser = User::create([
                'username' => $input['username'],
                'user_type_id' => $userTypeId,
                'name' => empty($input['name']) ? null : $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'password' => Hash::make($input['password']),
                'gender' => $input['gender'],
                'address' => $input['address'],
                'region' => $input['region'],
                'province' => $input['province'] ?? null,
                'city' => $input['city'],
                'barangay' => $input['barangay'],
                'postal_code' => $input['postal_code'],
            ]);

            UserDriver::create([
                'id' => $newUser->id,
                'status_id' => $inActiveStatusId,
                'shift' => $input['shift'],
                'license_number' => $input['license_number'],
                'license_expiry'=> $input['license_expiry'],
                'front_license_picture' => $frontIdPath,
                'back_license_picture' => $backIdPath,
                'nbi_clearance' => $nbiClearancePath,
                'selfie_picture' => $selfiePicturePath,
            ]);
            // Return the new user from the closure
            return $newUser;
        });

        // 3. Return the created user
        return $user;
    }

    protected function createPassenger(array $input, int $userTypeId): User
    {
        // 1. Validation
        Validator::make($input, [
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique(User::class),
                Rule::unique('franchises', 'email')
            ],
            'phone' => [
                'required', 'string', 'max:20',
                Rule::unique(User::class),
                Rule::unique('franchises', 'phone')
            ],
            'password' => $this->passwordRules(),
            'gender' => ['required', new Enum(Gender::class)],
            'birth_date' => ['required','date','before_or_equal:' . now()->subYears(10)->toDateString(),'after_or_equal:' . now()->subYears(100)->toDateString(),],         
            'address' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255', 'required_unless:region,NCR'],
            'city' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'terms1' => ['required', 'accepted'],
            'terms2' => ['required', 'accepted'],
        ])->validate();
        
        Validator::make(
            ['user_type_id' => $userTypeId],
            ['user_type_id' => ['required', 'integer', Rule::exists('user_types', 'id')]]
        )->validate();

            // 2. Create Records in a Transaction
        $user = DB::transaction(function () use ($input, $userTypeId) {

            // 'active' status ID is 1, default for passengers
            $activeStatusId = 1;

            // 2b. Create User
            $newUser = User::create([
                'username' => $input['username'],
                'user_type_id' => $userTypeId,
                'name' => empty($input['name']) ? null : $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'password' => Hash::make($input['password']),
                'gender' => $input['gender'],
                'address' => $input['address'],
                'region' => $input['region'],
                'province' => $input['province'] ?? null,
                'city' => $input['city'],
                'barangay' => $input['barangay'],
                'postal_code' => $input['postal_code'],
            ]);
      
            UserPassenger::create([
                'id' => $newUser->id,
                'status_id' => $activeStatusId,
                'birth_date' => $input['birth_date'],
            ]);
            // Return the new user from the closure
            return $newUser;
        });

        return $user;
    }

    protected function createTechnician(array $input, int $userTypeId): User
    {
        // 1. Validation
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique(User::class),
                Rule::unique('franchises', 'email')
            ],
            'phone' => [
                'required', 'string', 'max:20',
                Rule::unique(User::class),
                Rule::unique('franchises', 'phone')
            ],
            'password' => $this->passwordRules(),
            'gender' => ['required', new Enum(Gender::class)],
            'birth_date' => ['required','date','before_or_equal:' . now()->subYears(10)->toDateString(),'after_or_equal:' . now()->subYears(100)->toDateString(),],         
            'address' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255', 'required_unless:region,NCR'],
            'city' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'expertise'=> ['required', new Enum(Expertise::class)],
            'year_experience'=> ['required','integer', 'between:0,100'],
            'valid_id_type'=> ['required', new Enum(IdType::class)],
            'valid_id_number'=> ['required','string','max:20'],
            'certificate_prc_no' => ['nullable','file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'professional_license' => ['nullable','file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'front_valid_id_picture' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'back_valid_id_picture' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'cv_attachment' => ['required','file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'terms1' => ['required', 'accepted'],
            'terms2' => ['required', 'accepted'],
        ])->validate();

        Validator::make(
            ['user_type_id' => $userTypeId],
            ['user_type_id' => ['required', 'integer', Rule::exists('user_types', 'id')]]
        )->validate();

        // 2. Create Records in a Transaction
        $user = DB::transaction(function () use ($input, $userTypeId) {

            // 'pending' status ID is 6, default for technicians
            $pendingStatusId = 6;

            // 2a. Store all files
            $frontIdPath = $input['front_valid_id_picture']->store('technician_ids', 'public');
            $backIdPath = $input['back_valid_id_picture']->store('technician_ids', 'public');
            $cvPath = $input['cv_attachment']->store('technician_documents', 'public');
            $prcPath = isset($input['professional_license']) ? $input['professional_license']->store('technician_documents', 'public') : null;
            $certificatePath = isset($input['certificate_prc_no']) ? $input['certificate_prc_no']->store('technician_documents', 'public') : null;

            // 2b. Create User
            $newUser = User::create([
                'user_type_id' => $userTypeId,
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'password' => Hash::make($input['password']),
                'gender' => $input['gender'],
                'address' => $input['address'],
                'region' => $input['region'],
                'province' => $input['province'] ?? null,
                'city' => $input['city'],
                'barangay' => $input['barangay'],
                'postal_code' => $input['postal_code'],
            ]);

            // Create Technician record
            UserTechnician::create([
                'id' => $newUser->id,
                'status_id' => $pendingStatusId,
                'expertise'=> $input['expertise'],
                'year_experience'=> $input['year_experience'],
                'certificate_prc_no' => $certificatePath,
                'professional_license' => $prcPath,
                'valid_id_type'=> $input['valid_id_type'],
                'valid_id_number'=> $input['valid_id_number'],
                'front_valid_id_picture' => $frontIdPath,
                'back_valid_id_picture' => $backIdPath,
                'cv_attachment' => $cvPath,
                'birth_date' => $input['birth_date'],
            ]);
             // Return the new user from the closure
            return $newUser;
        });

        return $user;
    }

    protected function createOwner(array $input, int $userTypeId): User
    {
        // 1. Validation
        Validator::make($input, [
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique(User::class),
                Rule::unique('franchises', 'email')
            ],
            'phone' => [
                'required', 'string', 'max:20',
                Rule::unique(User::class),
                Rule::unique('franchises', 'phone')
            ],
            'password' => $this->passwordRules(),
            'home_region' => ['required', 'string', 'max:255'],
            'home_province' => ['nullable', 'string', 'max:255', 'required_unless:home_region,NCR'],
            'home_city' => ['required', 'string', 'max:255'],
            'home_barangay' => ['required', 'string', 'max:255'],
            'home_postal_code' => ['required', 'string', 'max:20'],
            'home_address' => ['required', 'string', 'max:255'],
            'franchise_name' => ['required', 'string', 'max:255'],
            'franchise_region' => ['required', 'string', 'max:255'],
            'franchise_province' => ['nullable', 'string', 'max:255', 'required_unless:franchise_region,NCR'],
            'franchise_city' => ['required', 'string', 'max:255'],
            'franchise_barangay' => ['required', 'string', 'max:255'],
            'franchise_postal_code' => ['required', 'string', 'max:20'],
            'franchise_address' => ['required', 'string', 'max:255'],
            'valid_id_type' => ['required', 'string', Rule::in(['National ID', 'Passport', 'Driver License', 'Voter ID', 'Unified Multi-Purpose ID', 'TIN ID'])],
            'valid_id_number' => ['required', 'string', 'max:20', Rule::unique('user_owners', 'valid_id_number')],
            'front_valid_id_picture' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'back_valid_id_picture' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'dti_certificate' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'mayor_permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'proof_capital' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'terms1' => ['required', 'accepted'],
            'terms2' => ['required', 'accepted'],
        ])->validate();

        Validator::make(
            ['user_type_id' => $userTypeId],
            ['user_type_id' => ['required', 'integer', Rule::exists('user_types', 'id')]]
        )->validate();

        // 2. Create Records in a Transaction
        $user = DB::transaction(function () use ($input, $userTypeId) {

            // 'pending' status ID is 6, default for owner and franchise
            $pendingStatusId = 6;

            // 2a. Store all files
            $frontIdPath = $input['front_valid_id_picture']->store('owner_ids', 'public');
            $backIdPath = $input['back_valid_id_picture']->store('owner_ids', 'public');
            $dtiPath = $input['dti_certificate']->store('franchise_documents', 'public');
            $mayorPermitPath = $input['mayor_permit']->store('franchise_documents', 'public');
            $proofAgreementPath = $input['proof_capital']->store('franchise_documents', 'public');

            // 2b. Create User
            $newUser = User::create([
                'username' => $input['username'],
                'user_type_id' => $userTypeId,
                'name' => empty($input['name']) ? null : $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'password' => Hash::make($input['password']),
                'address' => $input['home_address'],
                'region' => $input['home_region'],
                'province' => $input['home_province'] ?? null,
                'city' => $input['home_city'],
                'barangay' => $input['home_barangay'],
                'postal_code' => $input['home_postal_code'],
            ]);

            // 2c. Create UserOwner
            $userOwner = UserOwner::create([
                'id' => $newUser->id, // Use the new user's ID
                'status_id' => $pendingStatusId,
                'valid_id_type' => $input['valid_id_type'],
                'valid_id_number' => $input['valid_id_number'],
                'front_valid_id_picture' => $frontIdPath,
                'back_valid_id_picture' => $backIdPath,
            ]);

            // 2d. Create Franchise
            Franchise::create([
                'owner_id' => $userOwner->id, // Use the ID from the created owner
                'status_id' => $pendingStatusId,
                'name' => $input['franchise_name'],
                'email' => $input['email'], // Same as user
                'phone' => $input['phone'], // Same as user
                'address' => $input['franchise_address'],
                'region' => $input['franchise_region'],
                'province' => $input['franchise_province'] ?? null,
                'city' => $input['franchise_city'],
                'barangay' => $input['franchise_barangay'],
                'postal_code' => $input['franchise_postal_code'],
                'dti_registration_attachment' => $dtiPath,
                'mayor_permit_attachment' => $mayorPermitPath,
                'proof_agreement_attachment' => $proofAgreementPath,
            ]);

            // Return the new user from the closure
            return $newUser;
        });

        // 3. Return the created user
        return $user;
    }

    protected function createDefault(array $input, int $userTypeId): ?User
    {
        return null;
    }
}
