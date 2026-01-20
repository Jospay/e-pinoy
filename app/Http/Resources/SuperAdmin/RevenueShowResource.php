<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class RevenueShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 1. Basic Fields
        $data = [
            'id'             => $this->id,
            'invoice_no'     => $this->invoice_no,
            'amount'          => (float) $this->amount,
            'payment_date' => Carbon::parse($this->payment_date)->format('M d, Y h:i A'),
            'driver_username' => optional($this->driver?->user)->username ?? 'N/A',
        ];

        return $data;
    }
}
