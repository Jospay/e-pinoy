<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccreditationDatatableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name, // Now this shows "Taxi Car", "Bus", etc.
            // Map the franchises that are linked to this type via the seeder
            'accredited_types' => $this->franchises->map(function ($franchise) {
                return [
                    'type_name' => $franchise->name, // Shows the Franchise Name in the badge
                    'status_id' => $franchise->pivot->status_id,
                    'status_label' => $franchise->pivot->status_id == 1 ? 'Active' : 'Pending',
                ];
            }),
        ];
    }
}
