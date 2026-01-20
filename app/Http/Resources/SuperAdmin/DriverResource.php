<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DriverResource extends JsonResource
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
            'status' => $this->status->name,
            'code_number' => $this->code_number,
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
            'license_number' => $this->license_number,
            'license_expiry' => $this->license_expiry ? date('F d, Y', strtotime($this->license_expiry)) : 'N/A',
            'front_license_picture' => $this->front_license_picture ? Storage::url($this->front_license_picture) : null,
            'back_license_picture' => $this->back_license_picture ? Storage::url($this->back_license_picture) : null,
            'nbi_clearance' => $this->nbi_clearance ? Storage::url($this->nbi_clearance) : null,
            'selfie_picture' => $this->selfie_picture ? Storage::url($this->selfie_picture) : null,
            'shift' => $this->shift,
            'hire_date' => $this->hire_date ? date('F d, Y', strtotime($this->hire_date)) : 'N/A',
            'created_at' => $this->created_at ? $this->created_at->format('F d, Y') : 'N/A',
        ];
    }
}
