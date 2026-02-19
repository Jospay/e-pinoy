<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'franchise_name' => $this->name,
            'stations' => $this->busStations->map(fn ($s) => [
                'id'        => $s->id,
                'name'      => $s->name,
                'code_no'   => $s->code_no,
                'status'    => $s->status?->name ?? 'N/A',
                'latitude'  => $s->latitude ? (float) $s->latitude : null,
                'longitude' => $s->longitude ? (float) $s->longitude : null,
            ])->values(),
        ];
    }
}
