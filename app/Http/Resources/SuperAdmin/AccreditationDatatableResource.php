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
            'name' => $this->name, // "Taxi Car", "Bus", etc.

            'accredited_types' => $this->franchises->map(function ($franchise) {
                return [
                    'type_name' => $franchise->name,
                    'status_id' => (int) $franchise->pivot->status_id,
                    'status_label' => match ((int) $franchise->pivot->status_id) {
                        1 => 'Active',
                        6 => 'Pending',
                        default => 'Deny',
                    },
                ];
            }),
        ];
    }
}
