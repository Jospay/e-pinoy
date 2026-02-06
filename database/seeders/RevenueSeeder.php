<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;
use App\Models\Revenue;
use App\Models\Vehicle;
use App\Models\BoundaryContract;
use App\Models\UserPassenger;
use App\Models\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RevenueSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get Boundary Contracts with associated Driver and Vehicle
        $contracts = BoundaryContract::all();
        $passengerIds = UserPassenger::pluck('id');

        foreach ($contracts as $contract) {
            
            // 2. Fetch the Driver's specific Vehicle Type ID
            // This is the "Golden Thread" for data integrity.
            $driverVehicleType = DB::table('user_driver_vehicle_type')
                ->where('user_driver_id', $contract->driver_id)
                ->first();

            if (!$driverVehicleType) continue;

            $vehicleTypeId = $driverVehicleType->vehicle_type_id;

            // 3. Validate Vehicle Existence for this driver
            $vehicle = Vehicle::where('driver_id', $contract->driver_id)
                              ->where('franchise_id', $contract->franchise_id)
                              ->first();

            if (!$vehicle || $vehicle->status_id !== 1) {
                continue;
            }

            // 4. Generate random number of trips
            $numberOfTrips = rand(5, 12); 

            for ($i = 0; $i < $numberOfTrips; $i++) {
                $rand = rand(1, 100);

                // Default: Paid trip
                $statusId = 8; // Paid
                $tripDate = Carbon::parse(fake()->dateTimeBetween($contract->start_date, 'now'));
                $paymentDate = $tripDate; 
                $routeStatus = 14; // end_trip (from your StatusSeeder)

                // 10% chance: Pending (Live)
                if ($rand > 80 && $rand <= 90) {
                    $statusId = 6; // Pending
                    $tripDate = Carbon::now();
                    $paymentDate = null;
                    $routeStatus = 13; // start_trip
                }
                // 10% chance: Cancelled
                elseif ($rand > 90) {
                    $statusId = 9; // cancelled
                    $paymentDate = null;
                    $routeStatus = 9; // cancelled
                }
                
                // --- STEP A: CREATE REVENUE ---
                $revenue = Revenue::create([
                    'status_id'            => $statusId,
                    'franchise_id'         => $contract->franchise_id,
                    'vehicle_type_id'      => $vehicleTypeId, // DATA INTEGRITY MATCH
                    'driver_id'            => $contract->driver_id,
                    'boundary_contract_id' => null,
                    'payment_option_id'    => rand(1, 4),
                    'invoice_no'           => 'INV-' . strtoupper(Str::random(10)),
                    'amount'               => $this->getFare($vehicleTypeId),
                    'currency'             => 'PHP',
                    'service_type'         => 'Trips',
                    'payment_date'         => $paymentDate,
                    'notes'                => 'Auto-generated trip revenue',
                    'created_at'           => $tripDate,
                ]);

                // --- STEP B: CREATE ROUTE ---
                $startLat = fake()->latitude(15.1, 15.2);
                $startLng = fake()->longitude(120.55, 120.65);

                Route::create([
                    'status_id'           => $routeStatus,
                    'vehicle_type_id'     => $vehicleTypeId, // DATA INTEGRITY MATCH
                    'driver_id'           => $contract->driver_id,
                    'vehicle_id'          => $vehicle->id,
                    'passenger_id'        => $passengerIds->random(),
                    'revenue_id'          => $revenue->id,
                    'start_trip'          => ($statusId != 9) ? $tripDate : null,
                    'end_trip'            => ($statusId == 8) ? $tripDate->copy()->addMinutes(rand(10, 40)) : null,
                    'pickup_loc_name'     => fake()->streetAddress(),
                    'destination_loc_name'=> fake()->streetAddress(),
                    'start_lat'           => $startLat,
                    'start_lng'           => $startLng,
                    'end_lat'             => fake()->latitude(15.1, 15.2),
                    'end_lng'             => fake()->longitude(120.55, 120.65),
                    'distance_km'         => fake()->randomFloat(2, 1, 15),
                    'average_speed_kmh'   => fake()->randomFloat(2, 20, 50),
                    'created_at'          => $tripDate,
                ]);
            }
        }
    }

    /**
     * Helper to provide realistic fares based on vehicle type
     */
    private function getFare($typeId): float
    {
        return match ((int)$typeId) {
            1 => fake()->randomFloat(2, 150, 400), // Taxi
            2 => fake()->randomFloat(2, 30, 100),   // Bus (per passenger)
            3 => fake()->randomFloat(2, 40, 120),  // Tricycle
            default => 50.00,
        };
    }
}
