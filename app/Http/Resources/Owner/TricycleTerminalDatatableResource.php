<?php

namespace App\Http\Resources\Owner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TricycleTerminalDatatableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status_name' => $this->status?->name,

            'source_type' => $this->branch_id
                ? 'Branch'
                : 'Franchise',

            'source_name' => $this->branch_id
                ? $this->branch?->name
                : $this->franchise?->name,

            'full_address' => trim(
                "{$this->street}, {$this->barangay}, {$this->city}, {$this->province} {$this->postal_code}"
            ),
        ];
    }
}
