<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ExpenseShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id'             => $this->id,
            'invoice_no'     => $this->invoice_no,
            'amount'          => (float) $this->amount,
            'payment_date' => Carbon::parse($this->payment_date)->format('M d, Y h:i A'),
            'plate_number' => optional($this->maintenance?->vehicle)->plate_number ?? 'N/A',
            'description' => optional($this->maintenance)->description ?? 'N/A',
            'inventory_name' => optional($this->maintenance)->inventory->name ?? 'N/A',
        ];

        return $data;
    }
}
