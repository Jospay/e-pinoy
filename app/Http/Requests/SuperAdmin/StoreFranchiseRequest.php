<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class StoreFranchiseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
            'password' => $this->customPasswordRules(),
            'region' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255', 'required_unless:region,NCR'],
            'city' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
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
            
        ];
    }

    protected function customPasswordRules(): array
    {
        return [
            'required', 
            'string', 
            'min:8', 
            'confirmed',
            Password::min(8)
                ->mixedCase() 
                ->numbers()
                ->symbols(),
            'regex:/[\d\W_]/'
        ];
    }
}
