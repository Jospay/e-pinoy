<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Franchise;

class BusStationSeeder extends Seeder
{
    public function run(): void
    {
        $franchises = Franchise::all();

        if ($franchises->isEmpty()) {
            return;
        }

        foreach ($franchises as $franchise) {
            $stationCount = rand(3, 6);

            for ($i = 1; $i <= $stationCount; $i++) {
                DB::table('bus_stations')->insert([
                    'franchise_id' => $franchise->id,
                    'status_id'    => 1,
                    'name'         => $franchise->name . ' Station ' . $i,
                    'code_no'      => strtoupper(substr($franchise->name, 0, 3)) . '-' . $i,
                    'latitude'     => fake()->latitude(),
                    'longitude'    => fake()->longitude(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }
}
