<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vehicle_types')->insert([
            [
                'name'       => 'Taxi Car',
            ],
            [
                'name'       => 'Bus',
            ],
            [
                'name'       => 'Tricycle',
            ],
        ]);
    }
}
