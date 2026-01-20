<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoundaryContractDatatableResource extends JsonResource
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
            'start_date' => $this->start_date ? date('F j, Y', strtotime($this->start_date)) : 'N/A',
            'end_date' => $this->end_date ? date('F j, Y', strtotime($this->end_date)) : 'N/A',
            'driver_username' => $this->whenLoaded('driver', $this->driver->user->username),
            'status_name' => $this->whenLoaded('status', $this->status->name),
            'franchise_name' => $this->whenLoaded('franchise', fn () => $this->franchise?->name),
        ];

        return $data;
    }
}
