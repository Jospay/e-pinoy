<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Revenue;
use App\Models\PercentageType;

class RevenueBreakdownSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all percentage types (tax, bank, markup_fee, system_fee)
        $percentageTypes = PercentageType::all();

        // Get all revenues with service_type = Trips, paid status, and not null payment_date
        $revenues = Revenue::where('service_type', 'Trips')
            ->where('status_id', 8)
            ->whereNotNull('payment_date')
            ->get();

        foreach ($revenues as $revenue) {
            foreach ($percentageTypes as $type) {
                $amount = 0;

                if ($type->type === 'Percentage') {
                    // Compute percentage of revenue amount
                    $amount = ($revenue->amount * $type->value) / 100;
                } else {
                    // Fixed PHP fee
                    $amount = $type->value;
                }

                DB::table('revenue_breakdowns')->insert([
                    'revenue_id'        => $revenue->id,
                    'percentage_type_id'=> $type->id,
                    'total_earning'     => $amount,
                    'created_at'        => $revenue->payment_date,
                    'updated_at'        => now(),
                ]);
            }
        }
    }
}
