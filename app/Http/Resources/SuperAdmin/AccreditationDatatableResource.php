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
        $vehicleType = $this->vehicleTypes->first();
        $statusId    = $vehicleType?->pivot->status_id;
        $statusName  = static::$statusMap?->get($statusId)?->name ?? 'N/A';

        return [
            'id'             => $this->id,
            'franchise_name' => $this->name,
            'vehicle_type'   => $vehicleType?->name,
            'status_name'    => $statusName,
        ];
    }
}