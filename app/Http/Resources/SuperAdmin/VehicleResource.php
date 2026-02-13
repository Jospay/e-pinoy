<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'franchise_id' => $this->franchise_id,
            'plate_number' => $this->plate_number,
            'vin' => $this->vin,
            'brand' => $this->brand,
            'model'=> $this->model,
            'year' => $this->year,
            'color' => $this->color,
            'status' => $this->status->name,
            'or_cr' => $this->or_cr
            ? asset('storage/vehicle_documents/' . $this->or_cr)
            : null,
            'capacity' => $this->capacity,
            'vehicle_type' => ucfirst($this->vehicleType->name)
        ];

        if ($this->franchise) {
            $data['franchise_name'] = $this->franchise->name;
        } elseif ($this->branch) {
            $data['branch_name'] = $this->branch->name;
        }

        return $data;
    }
}
