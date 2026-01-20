<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class EarningShowResource extends JsonResource
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
            'total_amount'   => (float) $this->total_amount,
            'driver_earning' => (float) $this->driver_earning,
            'payment_date' => Carbon::parse($this->payment_date)->format('M d, Y h:i A'),
            'fees'           => [],
        ];

        // 2. Dynamic Fee Mapping
        $attributes = $this->resource->getAttributes();
        
        // We know standard columns, so we filter them out to find the dynamic fee columns
        $standardColumns = ['id', 'invoice_no', 'payment_date', 'total_amount', 'driver_earning', 'amount'];

        foreach ($attributes as $key => $value) {
            if (!in_array($key, $standardColumns)) {
                // Assuming any extra column selected is a fee
                $data['fees'][$key] = (float) $value;
            }
        }

        return $data;
    }
}
