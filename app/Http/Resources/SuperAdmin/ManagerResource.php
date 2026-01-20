<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManagerResource extends JsonResource
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
            'username' => $this->user->username,
            'name' => $this->user->name ?? 'N/A',
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'address' => $this->user->address,
            'region' => $this->user->region,
            'city' => $this->user->city,
            'barangay' => $this->user->barangay, 
            'province' => $this->user->province ? $this->user->province : null,
            'postal_code' => $this->user->postal_code,
            'created_at' => $this->created_at->format('F d, Y'),
        ];
    }
}
