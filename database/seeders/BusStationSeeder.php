<?php

namespace Database\Seeders;

use App\Models\Franchise;
use App\Models\VehicleType;
use App\Models\BusStation;
use App\Models\StationAmount;
use Illuminate\Database\Seeder;

class BusStationSeeder extends Seeder
{
    public function run(): void
    {
        $busTypeId = VehicleType::where('name', 'bus')->first()?->id;

        if (!$busTypeId) return;

        $busFranchises = Franchise::whereHas('vehicleTypes', function ($query) use ($busTypeId) {
            $query->where('vehicle_types.id', $busTypeId)
                  ->where('franchise_vehicle_type.status_id', 1);
        })->get();

        foreach ($busFranchises as $franchise) {
            
            // Define base locations
            $locations = [
                'angeles' => ['lat' => 15.1450, 'lng' => 120.5887],
                'middle'  => ['lat' => 15.0900, 'lng' => 120.6150],
                'sf'      => ['lat' => 15.0333, 'lng' => 120.6833],
            ];

            // Helper to add random jitter (approx +/- 500 meters)
            $jitter = function($val) {
                return $val + (mt_rand(-100, 100) / 10000);
            };

            $stationsData = [
                [
                    'name' => "{$franchise->name} - Angeles Terminal",
                    'code_no' => "AC-" . $franchise->id . "-" . str($franchise->name)->slug(),
                    'lat' => $jitter($locations['angeles']['lat']),
                    'lng' => $jitter($locations['angeles']['lng']),
                ],
                [
                    'name' => "{$franchise->name} - Telabastagan Stop",
                    'code_no' => "TEL-" . $franchise->id . "-" . str($franchise->name)->slug(),
                    'lat' => $jitter($locations['middle']['lat']),
                    'lng' => $jitter($locations['middle']['lng']),
                ],
                [
                    'name' => "{$franchise->name} - San Fernando Hub",
                    'code_no' => "SF-" . $franchise->id . "-" . str($franchise->name)->slug(),
                    'lat' => $jitter($locations['sf']['lat']),
                    'lng' => $jitter($locations['sf']['lng']),
                ],
            ];

            $createdStations = [];

            foreach ($stationsData as $data) {
                $createdStations[] = BusStation::create([
                    'franchise_id' => $franchise->id,
                    'status_id' => 1,
                    'name' => $data['name'],
                    'code_no' => $data['code_no'],
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                ]);
            }

            // Insert 1st to 2nd station amount
            StationAmount::create([
                'from_bus_station_id' => $createdStations[0]->id,
                'to_bus_station_id' => $createdStations[1]->id,
                'amount' => rand(15, 25), // Randomized fare per franchise
            ]);

            // Insert 2nd to 3rd station amount
            StationAmount::create([
                'from_bus_station_id' => $createdStations[1]->id,
                'to_bus_station_id' => $createdStations[2]->id,
                'amount' => rand(20, 35),
            ]);
        }
    }
}