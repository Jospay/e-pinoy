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
use Illuminate\Database\Seeder;

class RevenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ---------------------------------------------------------
        // PART 1: GENERATE BOUNDARY REVENUES (Strict Logic)
        // ---------------------------------------------------------
        
        // Fetch all active contracts
        $contracts = BoundaryContract::all();

        // Status IDs (Adjust based on your StatusSeeder)
        $STATUS_PAID = 8;    
        $STATUS_OVERDUE = 7; 

        foreach ($contracts as $contract) {
            // Create a period from Start Date until Today
            $period = CarbonPeriod::create($contract->start_date, Carbon::now());

            foreach ($period as $date) {
                // Randomize status: 80% chance Paid, 20% Overdue
                $isPaid = fake()->boolean(80); 
                $statusId = $isPaid ? $STATUS_PAID : $STATUS_OVERDUE;

                // Add random time to the date
                $randomDateTime = Carbon::parse($date)
                    ->setHour(rand(0, 23))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));

                Revenue::create([
                    'status_id' => $statusId,
                    'franchise_id' => $contract->franchise_id,
                    'driver_id' => $contract->driver_id,
                    'boundary_contract_id' => $contract->id,
                    'payment_option_id' => fake()->numberBetween(1, 4), // Cash, Gcash, etc
                    'invoice_no' => 'BND-' . strtoupper(Str::random(8)),
                    'amount' => $contract->amount, // STRICT: Must match contract
                    'currency' => 'PHP',
                    'service_type' => 'Boundary',
                    // If paid, date is the loop date. If overdue, date is null or the due date
                    'payment_date' => $isPaid ? $randomDateTime : null, 
                    'notes' => $isPaid ? 'Daily boundary remittance' : 'Missed payment',
                    'created_at' => $randomDateTime, // Backdate the creation
                    'updated_at' => $randomDateTime,
                ]);
            }
        }

        // ---------------------------------------------------------
        // PART 2: GENERATE TRIP REVENUES (Aligned Logic)
        // ---------------------------------------------------------
        
        // 1. Get Boundary Contracts (Because drivers MUST have a contract to have trip revenue)
        $contracts = BoundaryContract::with(['driver', 'vehicle'])->get();
        $passengerIds = UserPassenger::pluck('id');

        foreach ($contracts as $contract) {
            
            // 2. Validate Vehicle Status
            // Check if the vehicle associated with this contract/driver is valid for trips.
            // We assume only 'Active' (1) vehicles generate trip revenue. 
            // 'Maintenance' (5) or 'Available' (15) usually do not have trips.
            $vehicle = Vehicle::where('driver_id', $contract->driver_id)
                              ->where('id', $contract->vehicle_id)
                              ->first();

            // If driver has no vehicle, or vehicle is not Active, skip.
            if (!$vehicle || $vehicle->status_id !== 1) {
                continue;
            }

            // 3. Generate Trips for this specific Setup
            // Generate random number of trips within the contract period
            $numberOfTrips = rand(5, 15); 

            for ($i = 0; $i < $numberOfTrips; $i++) {

                // determine status and date
                $rand = rand(1, 100);

                // Default setup (Paid)
                $statusId = 8; // Paid
                $tripDate = Carbon::parse(fake()->dateTimeBetween($contract->start_date, 'now'))
                    ->setHour(rand(0, 23))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));
                $paymentDate = $tripDate; 
                $routeStatus = 13; // end_trip

                // Override for Pending (Live Trip) - 10% chance
                if ($rand > 80 && $rand <= 90) {
                    $statusId = 6; // Pending
                    $tripDate = Carbon::now(); // Must be NOW
                    $paymentDate = null;
                    $routeStatus = 12; // start_trip
                }
                // Override for Cancelled - 10% chance
                elseif ($rand > 90) {
                    $statusId = 9; // cancelled
                    $paymentDate = null;
                    // Keep tripDate historical to show when it was cancelled
                    $routeStatus = 9; // cancelled
                }
                
                // =========================================================
                // STEP A: CREATE REVENUE
                // =========================================================
                $revenue = Revenue::create([
                    'status_id'          => $statusId,
                    'franchise_id'       => $contract->franchise_id, // Inherit from contract
                    'driver_id'          => $contract->driver_id,
                    'boundary_contract_id' => null, // Trips don't link to boundary contracts ID directly
                    'payment_option_id'  => fake()->numberBetween(1, 4),
                    'invoice_no'         => 'TRP-' . strtoupper(Str::random(8)),
                    'amount'             => fake()->randomFloat(2, 50, 1500), // Typical trip fare
                    'currency'           => 'PHP',
                    'service_type'       => 'Trips',
                    'payment_date'       => $paymentDate,
                    'notes'              => 'Trip from ' . fake()->streetName() . ' to ' . fake()->streetName(),
                    'created_at'         => $tripDate,
                    'updated_at'         => $tripDate,
                ]);

                // =========================================================
                // STEP B: CREATE ROUTE (Linked to Revenue)
                // =========================================================
                
                // Geography Logic
                $startLat = fake()->latitude(15.1, 15.2); // Roughly Angeles, Pampanga
                $startLng = fake()->longitude(120.55, 120.65);
                $endLat   = fake()->latitude(15.1, 15.2);
                $endLng   = fake()->longitude(120.55, 120.65);
                $startTrip = Carbon::parse($tripDate);
                $endTrip = Carbon::parse($tripDate)->addMinutes(rand(15, 120));
                $distance = fake()->randomFloat(2, 2, 25);
                $speed = fake()->randomFloat(2, 15, 60);

                if ($statusId === 6) { 
                    // PENDING (In Progress)
                    $startTrip = Carbon::now(); // Started just now
                }
                elseif ($statusId === 9) {
                    // CANCELLED
                    $startTrip = null;
                    $endTrip = null;
                }

                Route::create([
                    'status_id'    => $routeStatus,
                    'driver_id'    => $contract->driver_id, // INTEGRITY: Same as Revenue
                    'vehicle_id'   => $contract->vehicle_id, // INTEGRITY: Assigned Vehicle
                    'passenger_id' => $passengerIds->random(), // Random Passenger
                    'revenue_id'   => $revenue->id, // LINKED
                    
                    'start_trip'   => $startTrip,
                    'end_trip'     => $endTrip,
                    
                    'start_lat'    => $startLat,
                    'start_lng'    => $startLng,
                    'end_lat'      => $endLat,
                    'end_lng'      => $endLng,
                    
                    'distance_km'       => $distance,
                    'average_speed_kmh' => $speed,
                    'max_speed_kmh'     => $speed ? $speed + 10 : null,
                    'route_path'        => null, // Complex JSON, keeping null for seeder
                    
                    'created_at'   => $tripDate,
                    'updated_at'   => $tripDate,
                ]);
            }
        }
    }
}
