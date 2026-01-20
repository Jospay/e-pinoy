<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverReportDatatableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // --- Handle Daily Period (ungrouped individual transactions) ---
        // This block handles the old 'daily' report or if a non-grouped query is run.
        if (isset($this->amount) && !isset($this->total_amount)) {
            return [
                'id' => $this->id,
                'invoice_no' => $this->invoice_no,
                'driver_id' => $this->driver_id,
                'payment_date' => $this->payment_date ? date('F j, Y', strtotime($this->payment_date)) : null,
                'amount' => (float) $this->amount, // Use the 'amount' column
                'service_type' => $this->service_type,
                'branch_name' => $this->whenLoaded('branch', fn () => $this->branch?->name),
                'driver_username' => $this->whenLoaded('driver', fn () => $this->driverDetails?->name),
            ];
        }

        // --- Handle Weekly/Monthly/Daily Grouped Periods (uses 'total_amount' and joined attributes) ---

        // Base data for grouped
        $data = [
            'id' => null, // No single ID
            'invoice_no' => 'N/A', // Default to N/A for grouped data
            'amount' => (float) $this->total_amount, // Use 'total_amount'
            'driver_id' => $this->driver_id,
            'service_type' => $this->service_type,
            // Access the JOINed attributes directly.
            'branch_name' => $this->branch_name ?? null,
            'payment_date' => 'N/A', // Default
            'driver_username' => $this->driver_username ?? null,
        ];

        // NEW: Dynamically add breakdown totals from the raw grouped query results (e.g., breakdown_tax -> tax)
        foreach ($this->resource->getAttributes() as $key => $value) {
            if (str_starts_with($key, 'breakdown_')) {
                // e.g., 'breakdown_tax' becomes 'tax'
                $cleanKey = str_replace('breakdown_', '', $key);
                $data[strtolower($cleanKey)] = (float) $value;
            }
        }

        // Handle Monthly Grouping
        if (isset($this->month_name)) {
            $data['payment_date'] = $this->month_name; // e.g., "November 2025"
        }
        // Handle Weekly Grouping
        elseif (isset($this->week_start)) {
            $startF = date('F', strtotime($this->week_start));
            $startJ = date('j', strtotime($this->week_start));
            $endF = date('F', strtotime($this->week_end));
            $endJ = date('j', strtotime($this->week_end));
            $endY = date('Y', strtotime($this->week_end));

            $dateRange = $startF === $endF
                ? "{$startF} {$startJ} - {$endJ}, {$endY}"
                : date('M j', strtotime($this->week_start)).' - '.date('M j, Y', strtotime($this->week_end));

            $data['payment_date'] = $dateRange;
        }
        // Handle Daily Grouping (New Logic)
        elseif (isset($this->daily_date_sort)) {
            $data['payment_date'] = date('F j, Y', strtotime($this->daily_date_sort));
            $data['invoice_no'] = 'Daily Total'; // Change descriptor for grouped daily
        }

        return $data;
    }
}
