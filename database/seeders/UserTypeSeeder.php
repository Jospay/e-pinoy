<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('user_types')->insert([
            ['id' => 1, 'name' => 'super_admin'], 
            ['id' => 2, 'name' => 'owner'],
            ['id' => 3, 'name' => 'manager'],
            ['id' => 4, 'name' => 'driver'],
            ['id' => 5, 'name' => 'technician'],
            ['id' => 6, 'name' => 'passenger'],
        ]);
    }
}
