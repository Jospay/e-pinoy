<?php

namespace App\Http\Resources\SuperAdmin;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoundaryContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $vehicleType = $this->vehicleTypes->first();
        $statusId = $vehicleType?->pivot->status_id;
        $statusName = $statusId ? Status::find($statusId)?->name : 'N/A';

        $isBranch = $this->relationLoaded('branch') && $this->branch;

        return [
            'id' => $this->id,
            'name' => $this->name,
            // Use the pivot amount instead of $this->amount
            'amount' => $vehicleType?->pivot->amount ?? 0,
            'coverage_area' => $this->coverage_area,
            'contract_terms' => $this->contract_terms,
            'renewal_terms' => $this->renewal_terms,
            'start_date' => $this->start_date ? date('F j, Y', strtotime($this->start_date)) : 'N/A',
            'end_date' => $this->end_date ? date('F j, Y', strtotime($this->end_date)) : 'N/A',
            
            // Driver Info
            'driver_username' => $this->whenLoaded('driver', fn() => $this->driver->user->username),
            'driver_name' => $this->whenLoaded('driver', fn() => $this->driver->user->name ?? 'N/A'),
            'driver_email' => $this->whenLoaded('driver', fn() => $this->driver->user->email),
            'driver_phone' => $this->whenLoaded('driver', fn() => $this->driver->user->phone),
            
            'status_name' => $statusName,
            'vehicle_type' => ucfirst($vehicleType?->name),

            // Branch Specific Data
            'branch_name'  => $this->when($isBranch, fn() => $this->branch->name),
            'branch_email' => $this->when($isBranch, fn() => $this->branch->email),
            'branch_phone' => $this->when($isBranch, fn() => $this->branch->phone),

            // Franchise Data
            'franchise_name' => $isBranch 
                ? $this->branch->franchise?->name 
                : ($this->franchise?->name ?? 'N/A'),

            'franchise_email' => $this->when(!$isBranch, fn() => $this->franchise?->email),
            'franchise_phone' => $this->when(!$isBranch, fn() => $this->franchise?->phone),
        ];
    }
}
