<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\UserPassenger;
use Illuminate\Support\Carbon; // Import Carbon for age calculation

class UserPassengerFactory extends Factory
{
    protected $model = UserPassenger::class;

    public function definition(): array
    {
        // Ensure age and birth_date match
        $birthDate = fake()->dateTimeBetween('-80 years', '-18 years');

        return [
            'status_id' => 1,
            'birth_date' => $birthDate,
        ];
    }
}
