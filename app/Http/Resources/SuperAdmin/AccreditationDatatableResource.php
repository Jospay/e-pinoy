<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccreditationDatatableResource extends JsonResource
{
    // Shared across all instances in the collection — only fetched once
    protected static ?Collection $statusMap = null;

    public static function withStatusMap(Collection $statusMap): void
    {
        static::$statusMap = $statusMap;
    }

    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'franchise_name' => $this->name,
            'vehicle_types'   => $this->vehicleTypes->map(fn ($v) => [
                'id'     => $v->id,
                'name'   => $v->name,
                'status' => static::$statusMap?->get($v->pivot->status_id)?->name ?? 'N/A',
            ])->values(),
        ];
    }
}