<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserDriver;
use App\Models\Franchise;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class DriverAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $franchiseIds = Franchise::pluck('id')->toArray();

        // Get only 'active' drivers who are not yet assigned
        $drivers = UserDriver::where('status_id', 1) 
            ->get();

        foreach ($drivers as $driver) {
            if (!empty($franchiseIds)) {
                DB::table('franchise_user_driver')->insert([
                    'franchise_id' => fake()->randomElement($franchiseIds),
                    'user_driver_id' => $driver->id,
                ]);
            }
        }
    }
}
