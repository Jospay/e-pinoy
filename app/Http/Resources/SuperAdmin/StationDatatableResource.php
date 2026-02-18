<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationDatatableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'franchise_name' => $this->name,
            'stations'       => $this->busStations->map(fn ($s) => [
                'code'   => $s->code_no,
                'status' => $s->status?->name ?? 'N/A',
            ])->values(),
        ];
    }
}
