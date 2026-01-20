<?php

namespace Database\Seeders;

use App\Models\Franchise;
use App\Models\User;
use App\Models\UserTechnician;
use App\Models\Vehicle;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // fix value
        $this->call(UserTypeSeeder::class);
        $this->call(StatusSeeder::class);
        $this->call(PaymentOptionSeeder::class);

        User::factory()->create([
            'user_type_id' => 1,
            'username' => 'testexample',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory()->create([
            'user_type_id' => 1,
            'username' => 'juandelacruz',
            'name' => 'Juan Delacruz',
            'email' => 'juandelacruz@gmail.com',
        ]);

        User::factory()->create([
            'user_type_id' => 2,
            'username' => 'pedrodelacruz',
            'name' => 'Pedro Delacruz',
            'email' => 'pedrodelacruz@gmail.com',
        ]);

        User::factory()->create([
            'user_type_id' => 4,
            'username' => 'dibbertmeda',
            'name' => 'Prof Cordell Kerluke',
            'email' => 'nnienow@example.org',
            'phone' => '09264728199',
        ]);

        User::factory()->create([
            'user_type_id' => 4,
            'username' => 'verdagorczany',
            'name' => 'Westley Thiel',
            'phone' => '09914288276',
        ]);

        User::factory()->create([
            'user_type_id' => 6,
            'username' => 'Alexis D Boy',
            'name' => 'Alexis D Boy',
            'email' => 'potjud30@gmail.com',
            'phone' => '09304206320',
        ]);

        User::factory()->create([
            'user_type_id' => 6,
            'username' => 'Iverson',
            'name' => 'Iverson M Mamangun',
            'email' => 'mamanguniverson@gmail.com',
            'phone' => '09761772917',
        ]);

        User::factory()->create([
            'user_type_id' => 6,
            'username' => 'Joshua F Payumo',
            'name' => 'Joshua F Payumo',
            'email' => 'joshuapayumo2001@gmail.com',
            'phone' => '09101301920',
        ]);

        User::factory(30)->create();

        User::factory(20)->create([
            'user_type_id' => 4,
        ]);

        $this->call(DriverAssignmentSeeder::class);
        $this->call(VehicleSeeder::class);
        Vehicle::factory(10)->create([
            'driver_id' => null
        ]);

        User::factory(30)->create(['user_type_id' => 5]);
        $this->call(TechnicianAssignmentSeeder::class);

        $this->call(BoundaryContractSeeder::class);
        $this->call(RevenueSeeder::class);

        $this->call(PercentageTypeSeeder::class);
        $this->call(RevenueBreakdownSeeder::class);

        $this->call(InventorySeeder::class);
        $this->call(MaintenanceSeeder::class);

        $this->call(SupportTicketSeeder::class);

        $this->call(FeedbackSeeder::class);

    }
}
