<?php

namespace App\Http\Requests\Owner;

use App\Models\BoundaryContract;
use App\Models\Status;
use App\Models\UserDriver;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreBoundaryContractRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'coverage_area' => ['required', 'string', 'max:1000'],
            'contract_terms' => ['required', 'string', 'max:1000'],
            'renewal_terms' => ['required', 'string', 'max:1000'],

            'driver' => [
                'required',
                'integer',
                'exists:user_drivers,id',
                // Custom rule to check if driver is Active AND has no active contracts
                function ($attribute, $value, $fail) {
                    $activeStatusId = Status::where('name', 'active')->value('id');

                    // 1. Check if Driver has an existing Active Contract
                    $hasActiveContract = BoundaryContract::where('driver_id', $value)
                        ->where('status_id', $activeStatusId)
                        ->exists();

                    if ($hasActiveContract) {
                        $fail('The selected driver already has an active boundary contract.');
                    }

                    $hasActiveStatus = UserDriver::where('id', $value)
                        ->where('status_id', $activeStatusId)
                        ->exists();

                    if (!$hasActiveStatus) {
                        $fail('The selected driver is not active.');
                    }

                    $franchise = auth()->user()->ownerDetails?->franchises()->first();
                    $franchiseId = $franchise?->id;

                    // 2. Optional: Verify Driver belongs to the selected Franchise/Branch
                    $existsInEntity = false;

                    $existsInEntity = DB::table('franchise_user_driver')
                        ->where('franchise_id', $franchiseId)
                        ->where('user_driver_id', $value)
                        ->exists();


                    if (!$existsInEntity) {
                        $fail('The selected driver does not belong to the selected franchise or branch.');
                    }
                },
            ],

            // LOGIC: Vehicle validation
            'vehicle' => [
                'required',
                'integer',
                'exists:vehicles,id',
                // Custom rule: Available Status + Belongs to Entity + No Active Contract
                function ($attribute, $value, $fail) {
                    $availableStatusId = Status::where('name', 'available')->value('id');
                    $activeStatusId = Status::where('name', 'active')->value('id');

                    $franchise = auth()->user()->ownerDetails?->franchises()->first();
                    $franchiseId = $franchise?->id;

                    // 1. Check ownership
                    $vehicle = Vehicle::find($value);
                    if ($franchiseId && $vehicle->franchise_id != $franchiseId) {
                        $fail('The selected vehicle does not belong to this franchise.');
                        return;
                    }

                    // 2. Check Availability (Is it already in an active contract?)
                    $hasActiveContract = BoundaryContract::where('vehicle_id', $value)
                        ->where('status_id', $activeStatusId)
                        ->exists();

                    if ($hasActiveContract) {
                        $fail('The selected vehicle is currently assigned to another active contract.');
                    }

                    $hasAvailableStatus = Vehicle::where('id', $value)
                        ->where('status_id', $availableStatusId)
                        ->exists();

                    if (!$hasAvailableStatus) {
                        $fail('The selected vehicle is not available.');
                    }
                },
            ],
        ];
    }
}
