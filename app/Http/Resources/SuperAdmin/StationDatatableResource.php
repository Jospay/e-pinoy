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
        if ($this->relationLoaded('franchise')) {
            return [
                'id'             => $this->id,
                'branch_name'    => $this->name,
                'franchise_name' => $this->franchise?->name,
                'stations'       => $this->busStations->map(fn ($s) => [
                    'id'     => $s->id,
                    'name'   => $s->name,
                    'code'   => $s->code_no,
                    'status' => $s->status?->name ?? 'N/A',
                ])->values(),
            ];
        }

        return [
            'id'             => $this->id,
            'franchise_name' => $this->name,
            'stations'       => $this->busStations->map(fn ($s) => [
                'id'     => $s->id,
                'name'   => $s->name,
                'code'   => $s->code_no,
                'status' => $s->status?->name ?? 'N/A',
            ])->values(),
        ];
    }
}
