<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
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
            'code_no' => $this->code_no,
            'name' => $this->name,
            'category' => $this->category,
            'specification' => $this->specification,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'notes' => $this->notes,
            'franchise_name' => $this->whenLoaded('franchise', fn () => $this->franchise?->name),
        ];

        return $data;
    }
}
