<?php

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ExpenseDatatableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Base data for grouped
        $data = [
            'amount' => (float) $this->total_amount, // Use 'total_amount'
            'franchise_id' => $this->franchise_id ?? null,
            'franchise_name' => $this->franchise_name ?? null,
            'payment_date' => 'N/A', // Default
            'query_params'   => $this->calculateDateRange($this->resource),
        ];

        // Handle Monthly
        if (isset($this->month_name)) {
            $data['payment_date'] = $this->month_name; // e.g., "November 2025"
        }
        // Handle Weekly
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
        elseif (isset($this->payment_date)) {
            $data['payment_date'] = date('F j, Y', strtotime($this->payment_date));
        }

        return $data;
    }

    /**
     * Helper to determine precise DB query range based on the row type
     */
    private function calculateDateRange($resource): array
    {
        // 1. Weekly Logic (Fields exist from Index GroupBy)
        if (isset($resource->week_start) && isset($resource->week_end)) {
            return [
                'start' => Carbon::parse($resource->week_start)->format('Y-m-d'), // Ensure date-only format
                'end'   => Carbon::parse($resource->week_end)->format('Y-m-d'),   // Ensure date-only format
                'type'  => 'weekly'
            ];
        }

        // 2. Monthly Logic
        if (isset($resource->month_sort)) {
            // month_sort is usually the first day of the month (e.g. 2023-10-01)
            $date = Carbon::parse($resource->month_sort);
            return [
                'start' => $date->startOfMonth()->format('Y-m-d'),
                'end'   => $date->endOfMonth()->format('Y-m-d'),
                'type'  => 'monthly'
            ];
        }

        // 3. Daily Logic (Default)
        // For daily, start and end are the same day
        $date = Carbon::parse($resource->payment_date)->format('Y-m-d');
        return [
            'start' => $date,
            'end'   => $date,
            'type'  => 'daily'
        ];
    }
}
