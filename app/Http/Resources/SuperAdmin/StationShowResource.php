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
        $isBranch = $this->relationLoaded('franchise');
        $stations = $this->busStations;

        return [
            'franchise_name' => $isBranch
                ? ($this->franchise?->name ?? 'N/A') . ' — ' . $this->name
                : $this->name,
            'stations' => $stations->map(fn ($s) => [
                'id'        => $s->id,
                'name'      => $s->name,
                'code_no'   => $s->code_no,
                'status'    => $s->status?->name ?? 'N/A',
                'latitude'  => $s->latitude  ? (float) $s->latitude  : null,
                'longitude' => $s->longitude ? (float) $s->longitude : null,
            ])->values(),
            'fares' => $stations->flatMap(fn ($s) =>
                $s->fromAmounts->map(fn ($fare) => [
                    'from_id'   => $s->id,
                    'from_code' => $s->code_no,
                    'to_id'     => $fare->to_bus_station_id,
                    'to_code'   => $fare->toStation?->code_no ?? 'N/A',
                    'amount'    => number_format($fare->amount, 2),
                ])
            )->values(),
        ];
    }
}
