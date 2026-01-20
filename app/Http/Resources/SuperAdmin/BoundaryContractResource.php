<?php

namespace App\Http\Resources\SuperAdmin;

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
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'coverage_area' => $this->coverage_area,
            'contract_terms' => $this->contract_terms,
            'renewal_terms' => $this->renewal_terms,
            'start_date' => $this->start_date ? date('F j, Y', strtotime($this->start_date)) : 'N/A',
            'end_date' => $this->end_date ? date('F j, Y', strtotime($this->end_date)) : 'N/A',
            'driver_username' => $this->whenLoaded('driver', $this->driver->user->username),
            'driver_name' => $this->whenLoaded('driver', $this->driver->user->name ?? 'N/A'),
            'driver_email' => $this->whenLoaded('driver', $this->driver->user->email),
            'driver_phone' => $this->whenLoaded('driver', $this->driver->user->phone),
            'status_name' => $this->whenLoaded('status', $this->status->name),
            'franchise_name' => $this->whenLoaded('franchise', fn () => $this->franchise?->name),
            'franchise_email' => $this->whenLoaded('franchise', fn () => $this->franchise?->email),
            'franchise_phone' => $this->whenLoaded('franchise', fn () => $this->franchise?->phone),
        ];

        return $data;
    }
}
