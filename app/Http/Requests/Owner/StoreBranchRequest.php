<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Gender;
use App\Enums\IdType;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
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
            // --- Branch Details ---
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('franchises', 'email'),
                Rule::unique('branches', 'email')
            ],
            'phone' => [
                'required', 'string', 'max:20',
                Rule::unique('users', 'phone'),
                Rule::unique('franchises', 'phone'),
                Rule::unique('branches', 'phone')
            ],
            
            // Branch Address
            'region' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255', 'required_unless:region,NCR'],
            'city' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],

            // Branch Files
            'dti_certificate' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'mayor_permit' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            'proof_capital' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,docx,doc', 'max:5120'],
            
            // --- Manager Toggle ---
            'has_manager' => ['required', 'boolean'],

            // --- Manager Details (Required if has_manager is true) ---
            'manager.name' => ['exclude_unless:has_manager,true', 'required', 'string', 'max:255'],
            'manager.email' => ['exclude_unless:has_manager,true', 'required', 'email', 'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('franchises', 'email'),
                Rule::unique('branches', 'email')
            ],
            'manager.phone' => ['exclude_unless:has_manager,true', 'required', 'string', 'max:255',
                Rule::unique('users', 'phone'),
                Rule::unique('franchises', 'phone'),
                Rule::unique('branches', 'phone')
            ],
            'manager.password' => ['exclude_unless:has_manager,true', 'required', 'min:8', 'confirmed'],
            'manager.gender' => ['exclude_unless:has_manager,true', 'required', new Enum(Gender::class)],
            
            // Manager Address
            'manager.address' => ['exclude_unless:has_manager,true', 'required', 'string', 'max:255'],
            'manager.region' => ['exclude_unless:has_manager,true', 'required', 'string', 'max:255'],
            'manager.province' => ['exclude_unless:has_manager,true', 'nullable', 'string', 'max:255', 'required_unless:manager.region,NCR'],
            'manager.city' => ['exclude_unless:has_manager,true', 'required', 'string', 'max:255'],
            'manager.barangay' => ['exclude_unless:has_manager,true', 'required', 'string', 'max:255'],
            'manager.postal_code' => ['exclude_unless:has_manager,true', 'required', 'string', 'max:255'],

            // Manager ID Details
            'manager.valid_id_type' => ['exclude_unless:has_manager,true', 'required', 'string', new Enum(IdType::class)],
            'manager.valid_id_number' => ['exclude_unless:has_manager,true', 'required', 'string', 'max:20', Rule::unique('user_owners', 'valid_id_number')],
            'manager.front_valid_id_picture' => ['exclude_unless:has_manager,true', 'required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'manager.back_valid_id_picture' => ['exclude_unless:has_manager,true', 'required','file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
        
    }
}
