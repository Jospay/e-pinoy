<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FranchiseDatatableResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'contract_attachment' => $this->contract_attachment ? Storage::url($this->contract_attachment) : null,
            'status_name' => $this->status->name ?? null,
            'owner_id' => $this->owner_id,
            'owner_username' => $this->owner->user->username ?? null,
        ];
    }
}
