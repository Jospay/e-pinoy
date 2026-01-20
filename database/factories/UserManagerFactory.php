<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\UserManager;

class UserManagerFactory extends Factory
{
    protected $model = UserManager::class;

    public function definition(): array
    {
        $idTypes = ['National ID', 'Passport', 'Driver License', 'Voter ID', 'Unified Multi-Purpose ID', 'TIN ID'];

        return [
            'status_id' => 1,
            'valid_id_type' => fake()->randomElement($idTypes),
            'valid_id_number' => fake()->unique()->numerify('##########'),
            'front_valid_id_picture' => fake()->imageUrl(640, 480, 'id', true),
            'back_valid_id_picture' => fake()->imageUrl(640, 480, 'id', true),
        ];
    }
}