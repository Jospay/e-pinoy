<?php

namespace App\Http\Requests\Owner;

use App\Models\BoundaryContract;
use App\Models\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreBoundaryContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'coverage_area' => ['required', 'string', 'max:1000'],
            'contract_terms' => ['required', 'string', 'max:1000'],
            'renewal_terms' => ['required', 'string', 'max:1000'],

            // Driver validation
           'driver_id' => [
            'required',
            'integer',
            'exists:user_drivers,id',
            function ($attribute, $value, $fail) {
                $activeStatus = Status::where('name', 'Active')->first();

                // 1. Check for Active Contract
                $hasActiveContract = BoundaryContract::where('driver_id', $value)
                    ->whereHas('vehicleTypes', function ($query) use ($activeStatus) {
                        $query->where('boundary_contract_vehicle_type.status_id', $activeStatus?->id);
                    })
                    ->exists();

                if ($hasActiveContract) {
                    $fail('The selected driver already has an active boundary contract.');
                    return;
                }

                // 2. Check Franchise OR Branch Ownership
                $franchise = auth()->user()->ownerDetails?->franchises()->first();
                if (!$franchise) {
                    $fail('Franchise context not found.');
                    return;
                }

                $branchIds = $franchise->branches()->pluck('id');

                // Check if driver is in main franchise
                $inFranchise = DB::table('franchise_user_driver')
                    ->where('franchise_id', $franchise->id)
                    ->where('user_driver_id', $value)
                    ->exists();

                // Check if driver is in any of the franchise's branches
                $inBranch = DB::table('branch_user_driver') // Ensure this table name is correct
                    ->whereIn('branch_id', $branchIds)
                    ->where('user_driver_id', $value)
                    ->exists();

                if (!$inFranchise && !$inBranch) {
                    $fail('The selected driver does not belong to your franchise or its branches.');
                }
            },
        ],

            // Vehicle rate validation (status removed)
            'vehicle_rates' => ['required', 'array', 'min:1'],
            'vehicle_rates.*.vehicle_type_id' => [
                'required',
                'exists:vehicle_types,id'
            ],
            'vehicle_rates.*.amount' => [
                'required',
                'numeric',
                'min:0'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_rates.*.vehicle_type_id.required' =>
                'Please select a vehicle type.',
            'vehicle_rates.*.amount.required' =>
                'The amount is required.',
            'vehicle_rates.*.amount.numeric' =>
                'The amount must be a number.',
        ];
    }
}
