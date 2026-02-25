<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GpsTrackerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $firstVehicle = $this->vehicles->first();

        $data = [
            'id' => $this->id,
            'username' => $this->whenLoaded('user', $this->user->username),
            'plate_number' => $firstVehicle?->plate_number,
            'vehicle_type' => $firstVehicle?->vehicleType?->name,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'isOnline' => $this->is_online,
        ];

        // Conditionally add franchise_name if the relation is loaded and not empty
        if ($this->relationLoaded('franchises') && $this->franchises->isNotEmpty()) {
            // We'll just show the first one for the datatable cell
            $data['franchise_name'] = $this->franchises->first()->name;
        }

        // Conditionally add branch_name if the relation is loaded and not empty
        if ($this->relationLoaded('branches') && $this->branches->isNotEmpty()) {
            // We'll just show the first one for the datatable cell
            $data['branch_name'] = $this->branches->first()->name;
        }

        return $data;
    }
}
