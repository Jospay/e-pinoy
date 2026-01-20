<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverVerificationResource extends JsonResource
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
            'name' => $this->whenLoaded('user', $this->user->name ?? 'N/A'),
            'email' => $this->whenLoaded('user', $this->user->email),
            'phone' => $this->whenLoaded('user', $this->user->phone),
            'status_name' => $this->whenLoaded('status', $this->status->name),
            'license_number' => $this->license_number,
        ];

        return $data;
    }
}
