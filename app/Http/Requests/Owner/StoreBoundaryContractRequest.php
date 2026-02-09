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
                    $activeStatusId = Status::where('name', 'active')->value('id');

                    $hasActiveContract = BoundaryContract::where('driver_id', $value)
                        ->whereHas('vehicleTypes', function ($query) use ($activeStatusId) {
                            $query->where(
                                'boundary_contract_vehicle_type.status_id',
                                $activeStatusId
                            );
                        })
                        ->exists();

                    if ($hasActiveContract) {
                        $fail('The selected driver already has an active boundary contract.');
                    }

                    $franchise = auth()->user()->ownerDetails?->franchises()->first();

                    $existsInEntity = DB::table('franchise_user_driver')
                        ->where('franchise_id', $franchise?->id)
                        ->where('user_driver_id', $value)
                        ->exists();

                    if (!$existsInEntity) {
                        $fail('The selected driver does not belong to your franchise.');
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
