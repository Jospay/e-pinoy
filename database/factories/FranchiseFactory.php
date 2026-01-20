<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Franchise>
 */
class FranchiseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status_id' => 1,
            'name' => fake()->unique()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'address' => fake()->address(),
            'region' => fake()->state(),
            'province' => fake()->state(),
            'city' => fake()->city(),
            'barangay' => fake()->streetName(),
            'postal_code' => fake()->postcode(),
            'latitude' => fake()->latitude(15.1, 15.2), // Roughly Angeles, Pampanga
            'longitude' => fake()->longitude(120.55, 120.65),
            'contract_attachment' => fake()->imageUrl(640, 480, 'business', true),
            'dti_registration_attachment' => fake()->imageUrl(640, 480, 'business', true),
            'mayor_permit_attachment' => fake()->imageUrl(640, 480, 'permit', true),
            'proof_agreement_attachment' => fake()->imageUrl(640, 480, 'agreement', true),
        ];
    }
}
