<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BoundaryContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BoundaryContractSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get drivers already assigned to franchises
        $franchiseDrivers = DB::table('franchise_user_driver')->get();

        foreach ($franchiseDrivers as $assignment) {
            $driverId = $assignment->user_driver_id;
            $franchiseId = $assignment->franchise_id;

            // 2. Get the Driver's specific Vehicle Type
            $driverVehicleType = DB::table('user_driver_vehicle_type')
                ->where('user_driver_id', $driverId)
                ->first();

            // 3. Ensure the Driver has a Vehicle assigned (Integrity check)
            $vehicle = DB::table('vehicles')
                ->where('driver_id', $driverId)
                ->where('franchise_id', $franchiseId)
                ->first();

            // Only create a contract if driver has a type AND a vehicle assigned
            if (!$driverVehicleType || !$vehicle) {
                continue;
            }

            $startDate = Carbon::now()->subMonth();
            $endDate = Carbon::now()->addMonths(6);
            
            // Logic for amount based on type
            $boundaryAmount = match ((int)$driverVehicleType->vehicle_type_id) {
                1 => 500.00,  // Taxi
                2 => 2000.00, // Bus
                3 => 150.00,  // Tricycle
                default => 300.00,
            };

            // 4. Create the Parent Contract
            $contract = BoundaryContract::create([
                'franchise_id'   => $franchiseId,
                'driver_id'      => $driverId,
                'name'           => 'Contract: ' . fake()->word() . " - " . $driverId,
                'currency'       => 'PHP',
                'coverage_area'  => fake()->city(),
                'contract_terms' => 'Standard Boundary Agreement',
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'renewal_terms'  => 'Review every 6 months',
            ]);

            // 5. Populate the Pivot Table
            // This ensures the contract is specifically tied to the vehicle type the driver uses
            DB::table('boundary_contract_vehicle_type')->insert([
                'boundary_contract_id' => $contract->id,
                'vehicle_type_id'      => $driverVehicleType->vehicle_type_id,
                'status_id'            => 1, // active
                'amount'               => $boundaryAmount,
            ]);
        }
    }
}