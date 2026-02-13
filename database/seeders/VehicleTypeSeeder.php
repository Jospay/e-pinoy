<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicleTypes = [
            ['name' => 'taxi'],
            ['name' => 'bus'],
            ['name' => 'tricycle'],
        ];

        foreach ($vehicleTypes as $type) {
            VehicleType::create($type);
        }
    }
}
