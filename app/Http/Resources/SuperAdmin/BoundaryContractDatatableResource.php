<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoundaryContractDatatableResource extends JsonResource
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
        $pivotAmount = $vehicleType?->pivot->amount ?? 0;

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $pivotAmount,
            'start_date' => $this->start_date ? date('M. j, Y', strtotime($this->start_date)) : 'N/A',
            'end_date'   => $this->end_date ? date('M. j, Y', strtotime($this->end_date)) : 'N/A',
            'driver_username' => $this->whenLoaded('driver', $this->driver->user->username),
            'status_name' => $statusName,
            'franchise_name' => $this->whenLoaded('franchise', fn () => $this->franchise?->name),
            'branch_name' => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'vehicle_type' => $vehicleType?->name,
        ];

        return $data;
    }
}
