<?php

namespace App\Http\Requests\Manager;

use App\Models\UserTechnician;
use App\Models\Status;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreMaintenanceRequest extends FormRequest
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
            'inventory' => ['required', 'exists:inventories,id'],
            'description' => ['required', 'string', 'max:1000'],
            'maintenance_date' => ['required', 'date'],
            'next_maintenance_date' => ['required', 'date', 'after:maintenance_date'],

            'technician' => [
                'required',
                'integer',
                'exists:user_technicians,id',
                function ($attribute, $value, $fail) {
                    $activeStatusId = Status::where('name', 'active')->value('id');

                    $hasActiveStatus = UserTechnician::where('id', $value)
                        ->where('status_id', $activeStatusId)
                        ->exists();

                    if (!$hasActiveStatus) {
                        $fail('The selected technician is not active.');
                    }

                    $branch = auth()->user()->managerDetails?->branches()->first();
                    $branchId = $branch?->id;

                    $existsInEntity = false;

                    $existsInEntity = DB::table('branch_user_technician')
                        ->where('branch_id', $branchId)
                        ->where('user_technician_id', $value)
                        ->exists();


                    if (!$existsInEntity) {
                        $fail('The selected technician does not belong to the selected franchise or branch.');
                    }
                },
            ],

            'vehicle' => [
                'required',
                'integer',
                'exists:vehicles,id',
                function ($attribute, $value, $fail) {
                    $activeStatusId = Status::where('name', 'active')->value('id');

                    $branch = auth()->user()->managerDetails?->branches()->first();
                    $branchId = $branch?->id;

                    $vehicle = Vehicle::find($value);
                    if ($branchId && $vehicle->branch_id != $branchId) {
                        $fail('The selected vehicle does not belong to this branch.');
                        return;
                    }
                },
            ],
        ];
    }
}
