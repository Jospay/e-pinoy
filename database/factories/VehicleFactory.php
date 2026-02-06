<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Franchise;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 1. Get a random assignment from the pivot table
        // This record contains a driver and franchise that ARE already compatible
        $assignment = DB::table('franchise_user_driver')
            ->inRandomOrder()
            ->first();

        if (!$assignment) {
            // Fallback: If no assignments exist yet, we can't create a valid vehicle 
            // matching both. Make sure DriverAssignmentSeeder runs before this.
            throw new \Exception("No driver-franchise assignments found.");
        }

        // 2. Get the specific vehicle_type_id that the driver is registered for
        // (Remember: Driver only has one vehicle type) for now
        $vehicleTypeId = DB::table('user_driver_vehicle_type')
            ->where('user_driver_id', $assignment->user_driver_id)
            ->value('vehicle_type_id');

        // 3. Determine Status (1: Active, 5: Maintenance, 15: Available)
        $statusId = $this->faker->randomElement([1, 1, 1, 5, 15]);
        
        // If "Available", the vehicle belongs to a franchise but has no driver
        $driverId = ($statusId === 15) ? null : $assignment->user_driver_id;

        return [
            'status_id'       => $statusId,
            'franchise_id'    => $assignment->franchise_id,
            'vehicle_type_id' => $vehicleTypeId,
            'driver_id'       => $driverId,
            'plate_number'    => strtoupper($this->faker->unique()->bothify('?? ###??')),
            'vin'             => strtoupper(Str::random(17)),
            'capacity'        => $this->getCapacity($vehicleTypeId), // Logic below
            'brand'           => $this->faker->randomElement(['Toyota', 'Honda', 'Isuzu', 'Mitsubishi']),
            'model'           => $this->faker->word(),
            'year'            => $this->faker->year(),
            'color'           => $this->faker->safeColorName(),
            'or_cr'           => $this->faker->imageUrl(640, 480, 'document', true),
        ];
    }

    /**
     * Helper to set capacity based on vehicle type name/id
     */
    private function getCapacity($typeId): int
    {
        return match ((int)$typeId) {
            1 => 4,  // Taxi
            2 => 50, // Bus
            3 => 3,  // Tricycle
            default => 4,
        };
    }
}
