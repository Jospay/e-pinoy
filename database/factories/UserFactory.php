<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDriver;
use App\Models\UserManager;
use App\Models\UserOwner;
use App\Models\UserPassenger;
use App\Models\UserTechnician;
use App\Models\Franchise;
use App\Models\VehicleType;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_type_id' => random_int(2, 6),
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->unique()->phoneNumber(),
            'password' => static::$password ??= 'password',
            'remember_token' => Str::random(10),
            // 'two_factor_secret' => Str::random(10),
            // 'two_factor_recovery_codes' => Str::random(10),
            // 'two_factor_confirmed_at' => now(),
            'address' => fake()->address(),
            'region' => fake()->state(),
            'province' => fake()->state(),
            'city' => fake()->city(),
            'barangay' => fake()->streetName(),
            'postal_code' => fake()->postcode(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Configure the model factory.
     *
     * This is the new method you need to add.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            // Use the User Type IDs from your UserTypeSeeder
            switch ($user->user_type_id) {
                case 1: // super_admin
                    // Do nothing
                    break;
                case 2: // owner
                    UserOwner::factory()->create(['id' => $user->id]);
                    $franchise = Franchise::factory()->create([
                        'owner_id' => $user->id, 
                        'email' => $user->email, 
                        'phone' => $user->phone
                    ]);
                    // --- PIVOT TABLE LOGIC ---
                    $vehicleTypeIds = VehicleType::inRandomOrder()->take(rand(1, 3))->pluck('id');
                    foreach ($vehicleTypeIds as $id) {
                        $franchise->vehicleTypes()->attach($id, [
                            'status_id' => 1 // active
                        ]);
                    }
                    break;
                case 3: // manager
                    UserManager::factory()->create(['id' => $user->id]);
                    break;
                case 4: // driver
                    $driver = UserDriver::factory()->create(['id' => $user->id]);
                    // --- PIVOT TABLE LOGIC ---
                    $randomVehicleTypeId = VehicleType::inRandomOrder()->first()->id;
                    $driver->vehicleTypes()->attach($randomVehicleTypeId);
                    break;
                case 5: // technician
                    UserTechnician::factory()->create(['id' => $user->id]);
                    break;
                case 6: // passenger
                    UserPassenger::factory()->create(['id' => $user->id]);
                    break;
            }
        });
    }
}
