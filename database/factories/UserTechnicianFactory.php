<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\UserTechnician;
use Illuminate\Support\Carbon;

class UserTechnicianFactory extends Factory
{
    protected $model = UserTechnician::class;

    public function definition(): array
    {
        $expertises = ['Mechanical', 'Electrical', 'Battery'];
        $idTypes = ['National ID', 'Passport', 'Driver License', 'Voter ID', 'Unified Multi-Purpose ID', 'TIN ID'];

        $birthDate = fake()->dateTimeBetween('-60 years', '-20 years');

        return [
            'status_id' => 1,
            'expertise' => fake()->randomElement($expertises),
            'year_experience' => random_int(1, 30),
            'certificate_prc_no' => fake()->optional()->numerify('PRC-#######'),
            'professional_license' => fake()->optional()->imageUrl(640, 480, 'license', true),
            'valid_id_type' => fake()->randomElement($idTypes),
            'valid_id_number' => fake()->unique()->numerify('##########'),
            'front_valid_id_picture' => fake()->imageUrl(640, 480, 'id', true),
            'back_valid_id_picture' => fake()->imageUrl(640, 480, 'id', true),
            'cv_attachment' => fake()->imageUrl(640, 480, 'document', true),
            'birth_date' => $birthDate,
        ];
    }
}
