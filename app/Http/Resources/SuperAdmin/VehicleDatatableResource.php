<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleDatatableResource extends JsonResource
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
            'vin' => $this->vin,
            'plate_number' => $this->plate_number,
            'status_name' => $this->whenLoaded('status', $this->status->name),
            'franchise_name' => $this->whenLoaded('franchise', fn () => $this->franchise?->name),
            'branch_name' => $this->whenLoaded('branch', fn () => $this->branch?->name),
        ];

        return $data;
    }
}
