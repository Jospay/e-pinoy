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
    // public function toArray(Request $request): array
    // {
    //     $data = [
    //         'id' => $this->id,
    //         'username' => $this->whenLoaded('user', $this->user->username),
    //         'name' => $this->whenLoaded('user', $this->user->name ?? 'N/A'),
    //         'email' => $this->whenLoaded('user', $this->user->email),
    //         'phone' => $this->whenLoaded('user', $this->user->phone),
    //         'status_name' => $this->whenLoaded('status', $this->status->name),
    //         'license_number' => $this->license_number,
    //         'branch_name' => $this->whenLoaded('branches', fn () => $this->branches->first()->name),
    //         'franchise_name' => $this->whenLoaded('franchises', fn () => $this->franchises->first()->name),
    //     ];

    //     return $data;
    // }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->whenLoaded('user', fn() => $this->user->username),
            'name' => $this->whenLoaded('user', fn() => $this->user->name ?? 'N/A'),
            'email' => $this->whenLoaded('user', fn() => $this->user->email),
            'phone' => $this->whenLoaded('user', fn() => $this->user->phone),
            'status_name' => $this->whenLoaded('status', fn() => $this->status?->name ?? 'Unknown'),
            'license_number' => $this->license_number,
            'branch_name' => $this->whenLoaded('branches', function () {
                return $this->branches->first()?->name ?? 'No Branch';
            }),
            'franchise_name' => $this->whenLoaded('franchises', function () {
                return $this->franchises->first()?->name ?? 'No Franchise';
            }),
        ];
    }
}
