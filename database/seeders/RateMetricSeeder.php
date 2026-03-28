<?php

namespace Database\Seeders;

use App\Models\RateMetric;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class RateMetricSeeder extends Seeder
{
    public function run(): void
    {
        // Define the types we want to support
        $types = ['taxi', 'tricycle'];

        foreach ($types as $typeName) {
            $type = VehicleType::where('name', $typeName)->first();

            if ($type) {
                RateMetric::firstOrCreate(
                    ['vehicle_type_id' => $type->id],
                    [
                        'flag' => 50.00,
                        'per_km' => 13.50,
                        'per_minute' => 2.00,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
