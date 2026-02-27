<?php

namespace App\Http\Resources\SuperAdmin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningDatatableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 1. Standard Fields
        $data = [
            'driver_id'      => $this->driver_id,
            'driver_username'    => $this->driver_username,
            'franchise_name' => $this->franchise_name ?? null,
            'branch_name'    => $this->branch_name ?? null,
            'total_amount'   => (float) $this->total_amount,
            'driver_earning' => (float) $this->driver_earning,
            'payment_date'   => 'N/A',
            'fees'           => [], // We will put dynamic fees here
            'query_params'   => $this->calculateDateRange($this->resource),
        ];

        // 2. Fetch all possible fee slugs (cached or passed ideally, but this works)
        // Note: For better performance, pass the types via constructor
        $attributes = $this->resource->getAttributes();
        
        foreach ($attributes as $key => $value) {
            // Check if attribute starts with 'total_' and isn't 'total_amount'
            if (str_starts_with($key, 'total_') && $key !== 'total_amount') {
                // Remove 'total_' prefix to get the slug (e.g. 'total_markup_fee' -> 'markup_fee')
                $slug = substr($key, 6); 
                $data['fees'][$slug] = (float) $value;
            }
        }

        // 3. Date Formatting (Same as before)
        if (isset($this->month_name)) {
            $data['payment_date'] = $this->month_name;
        } elseif (isset($this->week_start)) {
            $start = Carbon::parse($this->week_start);
            $end = Carbon::parse($this->week_end);
            $data['payment_date'] = ($start->month === $end->month)
                ? $start->format('M j') . ' - ' . $end->format('j, Y')
                : $start->format('M j') . ' - ' . $end->format('M j, Y');
        } else {
            $data['payment_date'] = Carbon::parse($this->payment_date)->format('M j, Y');
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
