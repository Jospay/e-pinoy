<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'maintenance_date' => $this->maintenance_date ? date('F j, Y', strtotime($this->maintenance_date)) : 'N/A',
            'next_maintenance_date' => $this->next_maintenance_date ? date('F j, Y', strtotime($this->next_maintenance_date)) : 'N/A',
            // Nested inventory details
            'inventory_name' => $this->inventory->name,
            'category' => $this->inventory->category,
            'specification' => $this->inventory->specification,
        ];
    }
}
