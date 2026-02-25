<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Status;
use App\Models\BoundaryContract;
use App\Models\Vehicle;
use App\Models\UserDriver;

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
            
            'franchise_id' => [
                'nullable',
                'integer',
                'exists:franchises,id'
            ],

            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id'
            ],

            // LOGIC: Driver validation
            'driver_id' => [
                'required',
                'integer',
                'exists:user_drivers,id',
                // Custom rule to check if driver is Active AND has no active contracts
                function ($attribute, $value, $fail) {
                    $activeStatusId = Status::where('name', 'active')->value('id');

                    // 1. Check if Driver has an existing Active Contract through pivot
                    $hasActiveContract = BoundaryContract::where('driver_id', $value)
                        ->whereHas('vehicleTypes', function ($q) use ($activeStatusId) {
                            $q->where('boundary_contract_vehicle_type.status_id', $activeStatusId);
                        })
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
                    
                    // 2. Optional: Verify Driver belongs to the selected Franchise
                    $existsInEntity = false;
                    if ($this->franchise_id) {
                         $existsInEntity = DB::table('franchise_user_driver')
                            ->where('franchise_id', $this->franchise_id)
                            ->where('user_driver_id', $value)
                            ->exists();
                    } elseif ($this->branch_id) {
                        $existsInEntity = DB::table('branch_user_driver')
                            ->where('branch_id', $this->branch_id)
                            ->where('user_driver_id', $value)
                            ->exists();
                    }

                    if (!$existsInEntity) {
                        $fail('The selected driver does not belong to the selected franchise or branch.');
                    }
                },
            ],

            // LOGIC: Vehicle type validation
            'vehicle_type_id' => [
                'required',
                'integer',
                'exists:vehicle_types,id',
                function ($attribute, $value, $fail) {
                    $activeStatusId = Status::where('name', 'active')->value('id');

                    // 1. Check if Vehicle Type is connected to franchise / branch
                    $existsInEntity = false;
                    if ($this->franchise_id) {
                        $existsInEntity = DB::table('franchise_vehicle_type')
                            ->where('franchise_id', $this->franchise_id)
                            ->where('vehicle_type_id', $value)
                            ->where('status_id', $activeStatusId)
                            ->exists();
                    } elseif ($this->branch_id) {
                        $existsInEntity = DB::table('branch_vehicle_type')
                            ->where('branch_id', $this->branch_id)
                            ->where('vehicle_type_id', $value)
                            ->where('status_id', $activeStatusId)
                            ->exists();
                    }

                    if (!$existsInEntity) {
                        $fail('The selected vehicle type does not belong to the selected franchise or branch.');
                    }

                    // 2. Check if Vehicle Type is connected to driver
                    $existsInEntity = DB::table('user_driver_vehicle_type')
                        ->where('user_driver_id', $this->driver_id)
                        ->where('vehicle_type_id', $value)
                        ->exists();

                    if (!$existsInEntity) {
                        $fail('The selected vehicle type is not connected to the selected driver.');
                    }
                },
            ],
        ];
    }
}
