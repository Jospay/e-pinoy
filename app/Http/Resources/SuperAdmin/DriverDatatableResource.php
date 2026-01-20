<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverDatatableResource extends JsonResource
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
            'username' => $this->whenLoaded('user', $this->user->username),
            'email' => $this->whenLoaded('user', $this->user->email),
            'phone' => $this->whenLoaded('user', $this->user->phone),
            'status_name' => $this->whenLoaded('status', $this->status->name),
        ];

        // Conditionally add franchise_name if the relation is loaded and not empty
        if ($this->relationLoaded('franchises') && $this->franchises->isNotEmpty()) {
            // We'll just show the first one for the datatable cell
            $data['franchise_name'] = $this->franchises->first()->name;
        }

        return $data;
    }
}