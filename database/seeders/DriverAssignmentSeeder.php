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
        // Get all active drivers with their assigned vehicle type
        $drivers = UserDriver::where('status_id', 1)->with('vehicleTypes')->get();

        foreach ($drivers as $driver) {
            // Get the driver's single vehicle type ID
            $driverVehicleTypeId = $driver->vehicleTypes->first()?->id;

            if (!$driverVehicleTypeId) continue;

            /** * Find franchises that have the SAME vehicle type 
             * and where the status in the pivot is 'active' (status_id 1)
             */
            $compatibleFranchiseIds = DB::table('franchise_vehicle_type')
                ->where('vehicle_type_id', $driverVehicleTypeId)
                ->where('status_id', 1) 
                ->pluck('franchise_id')
                ->toArray();

            if (!empty($compatibleFranchiseIds)) {
                // Assign to one random compatible franchise
                $assignedFranchiseId = fake()->randomElement($compatibleFranchiseIds);

                DB::table('franchise_user_driver')->insert([
                    'franchise_id'   => $assignedFranchiseId,
                    'user_driver_id' => $driver->id,
                ]);
            }
        }
    }
}
