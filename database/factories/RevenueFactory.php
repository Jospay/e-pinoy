<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Revenue>
 */
class RevenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array
    {
        // Only focus on random "Trips" here, or fallback data
        $franchiseId = null;
        $driverId = null;

        // Logic to find a valid driver assignment to avoid foreign key errors
        $record = DB::table('franchise_user_driver')->inRandomOrder()->first();
        if($record) {
            $franchiseId = $record->franchise_id;
            $driverId = $record->user_driver_id;
        }
      
        // Fallback if DB is empty (during testing)
        if (!$driverId) return []; 

        $statusId = $this->faker->randomElement([6, 8, 8, 8, 9]); // pick more paid status 8
        $paymentDate = $statusId === 8 ? $this->faker->dateTimeThisMonth() : null;

        return [
            'status_id' => $statusId,
            'franchise_id' => $franchiseId,
            'driver_id'    => $driverId,
            'payment_option_id' => $this->faker->numberBetween(1, 4),
            'invoice_no' => 'TRP-' . Str::upper(Str::random(6)),
            'amount' => $this->faker->randomFloat(2, 100, 3000), // Variable amounts for trips
            'currency' => 'PHP',
            'service_type' => 'Trips', // Default to trips in factory
            'payment_date' => $paymentDate,
            'notes' => $this->faker->sentence(),
        ];
    }
}
