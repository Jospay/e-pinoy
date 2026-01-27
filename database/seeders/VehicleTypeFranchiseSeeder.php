<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VehicleType;
use App\Models\Franchise;
use Illuminate\Support\Facades\DB;

class VehicleTypeFranchiseSeeder extends Seeder
{
    public function run(): void
    {
        $franchises = Franchise::all();
        $vehicleTypes = VehicleType::all();

        if ($franchises->isEmpty() || $vehicleTypes->isEmpty()) {
            return;
        }

        // Assign 1-2 random vehicle types to each franchise with status
        foreach ($franchises as $franchise) {
            $count = rand(1, min(2, $vehicleTypes->count()));
            $assignedVehicleTypes = $vehicleTypes->random($count);

            foreach ($assignedVehicleTypes as $vehicleType) {
                $statusId = rand(0, 1) ? 1 : 6;

                DB::table('franchise_vehicle_type')->insert([
                    'franchise_id' => $franchise->id,
                    'vehicle_type_id' => $vehicleType->id,
                    'status_id' => $statusId,
                ]);
            }
        }
    }
}
