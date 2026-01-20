<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Expense;

class TransactionDatatableResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'driver_username' => !$isExpense ? optional($this->driver?->user)->username : 'N/A',
            'date' => $this->status?->name === 'paid' && $this->payment_date
                ? date('F j, Y', strtotime($this->payment_date))
                : date('F j, Y', strtotime($this->created_at)),
            'franchise_name' => $this->whenLoaded('franchise', fn () => $this->franchise?->name),
        ];

        return $data;
    }
}
