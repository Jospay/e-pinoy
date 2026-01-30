<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Franchise;

class StationAmountSeeder extends Seeder
{
    public function run(): void
    {
        $franchises = Franchise::all();

        if ($franchises->isEmpty()) {
            return;
        }

        foreach ($franchises as $franchise) {

            // Get stations ONLY for this franchise
            $stations = DB::table('bus_stations')
                ->where('franchise_id', $franchise->id)
                ->orderBy('id')
                ->get();

            if ($stations->count() < 2) {
                continue;
            }

            // Chain stations: 1→2, 2→3, 3→4
            for ($i = 0; $i < $stations->count() - 1; $i++) {
                $from = $stations[$i];
                $to   = $stations[$i + 1];

                DB::table('station_amount')->updateOrInsert(
                    [
                        'first_bus_station_id'  => $from->id,
                        'second_bus_station_id' => $to->id,
                    ],
                    [
                        'amount'     => rand(10, 50),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
