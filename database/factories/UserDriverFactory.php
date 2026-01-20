<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\UserDriver;

class UserDriverFactory extends Factory
{
    protected $model = UserDriver::class;

    public function definition(): array
    {

        $statusId = fake()->randomElement([1, 1, 1, 2]);
        $latitude = fake()->latitude(15.1, 15.2); // Roughly Angeles, Pampanga
        $longitude = fake()->longitude(120.55, 120.65);
        $hireDate = fake()->dateTimeBetween('-3 months', 'now');

        return [
            // 'id' is intentionally omitted here
            // It will be provided by the UserFactory
            'status_id' => $statusId,
            'code_number' => $statusId == 1 ? fake()->unique()->bothify('??-####') : null,
            'license_number' => fake()->unique()->bothify('??-########'),
            'is_verified' => $statusId == 1,
            'is_online' => $statusId == 1,
            'latitude' => $statusId == 1 ? $latitude : null,
            'longitude' => $statusId == 1 ? $longitude : null,
            'license_expiry' => fake()->dateTimeBetween('+1 year', '+5 years'),
            'front_license_picture' => fake()->imageUrl(640, 480, 'license', true),
            'back_license_picture' => fake()->imageUrl(640, 480, 'license', true),
            'nbi_clearance' => fake()->imageUrl(640, 480, 'document', true),
            'selfie_picture' => fake()->imageUrl(640, 480, 'person', true),
            'hire_date' => $statusId == 1 ? $hireDate : null,
            'created_at' => $statusId == 1 ? $hireDate : now(),
            'updated_at' => $statusId == 1 ? $hireDate : now(),
        ];
    }
}