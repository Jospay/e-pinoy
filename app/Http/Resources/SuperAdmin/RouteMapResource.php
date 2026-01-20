<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteMapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'driver_name' => $this->driver->user->name ?? 'Unknown Driver',
            'plate_number' => $this->vehicle->plate_number ?? 'N/A',
            'payment_date' => $this->revenue->payment_date ? date('F j, Y', strtotime($this->revenue->payment_date)) : null,
            'latitude' => (float) $this->end_lat,
            'longitude' => (float) $this->end_lng,
        ];
    }
}