<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Expense;
use Carbon\Carbon;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isExpense = $this->resource instanceof Expense;

        $data = [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'status_name' => $this->whenLoaded('status', $this->status->name),
            'service_type' => $isExpense ? 'Maintenance' : $this->service_type,
            'payment_option' => $this->payment_option,
            'amount' => (float) $this->amount,
            'created_at' => $this->created_at ? date('M d, Y h:i A', strtotime($this->created_at)) : null,
            'payment_date' => $this->payment_date ? date('M d, Y h:i A', strtotime($this->payment_date)) : null,
            'franchise_name' => $this->whenLoaded('franchise', fn () => $this->franchise?->name),
            'payment_option' => $this->whenLoaded('paymentOption', $this->paymentOption->name),
            'notes' => $this->notes ?? null,
            // revenue data
            'driver_username' => !$isExpense ? optional($this->driver?->user)->username : 'N/A',
            // expense data
            'vehicle_plate' => $isExpense ? optional($this->maintenance?->vehicle)->plate_number : 'N/A',
            'description' => $isExpense ? optional($this->maintenance)->description : 'N/A',
            'maintenance_date' => $isExpense && optional($this->maintenance)->maintenance_date
                ? Carbon::parse($this->maintenance->maintenance_date)->format('M d, Y h:i A')
                : 'N/A',

            'inventory_name' => $isExpense ? optional($this->maintenance?->inventory)->name : 'N/A',
            'inventory_category' => $isExpense ? optional($this->maintenance?->inventory)->category : 'N/A',
        ];

        return $data;
    }
}
