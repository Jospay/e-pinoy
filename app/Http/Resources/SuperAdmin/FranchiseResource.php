<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FranchiseResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'region' => $this->region,
            'city' => $this->city,
            'barangay' => $this->barangay, 
            'province' => $this->province ? $this->province : null,
            'postal_code' => $this->postal_code, 
            'contract_attachment' => $this->contract_attachment ? Storage::url($this->contract_attachment) : null,
            'dti_registration_attachment' => $this->dti_registration_attachment ? Storage::url($this->dti_registration_attachment) : null,
            'mayor_permit_attachment' => $this->mayor_permit_attachment ? Storage::url($this->mayor_permit_attachment) : null,
            'proof_agreement_attachment' => $this->proof_agreement_attachment ? Storage::url($this->proof_agreement_attachment) : null,
            'created_at' => $this->created_at->format('F d, Y'),
        ];
    }
}
