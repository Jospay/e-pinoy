<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('day_schedules')->insert([
            ['id' => 1, 'name' => 'monday'],
            ['id' => 2, 'name' => 'tuesday'],
            ['id' => 3, 'name' => 'wednesday'],
            ['id' => 4, 'name' => 'thursday'],
            ['id' => 5, 'name' => 'friday'],
            ['id' => 6, 'name' => 'saturday'],
            ['id' => 7, 'name' => 'sunday'],
        ]);
    }
}
