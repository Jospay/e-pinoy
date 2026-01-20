<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('statuses')->insert([
            ['id' => 1, 'name' => 'active'],
            ['id' => 2, 'name' => 'inactive'],
            ['id' => 3, 'name' => 'suspended'],
            ['id' => 4, 'name' => 'retired'],
            ['id' => 5, 'name' => 'maintenance'],
            ['id' => 6, 'name' => 'pending'],
            ['id' => 7, 'name' => 'overdue'],
            ['id' => 8, 'name' => 'paid'],
            ['id' => 9, 'name' => 'cancelled'],
            ['id' => 10, 'name' => 'que'],
            ['id' => 11, 'name' => 'to_pick_up'],
            ['id' => 12, 'name' => 'confirm_pick_up'],
            ['id' => 13, 'name' => 'start_trip'],
            ['id' => 14, 'name' => 'end_trip'],
            ['id' => 15, 'name' => 'available'],
            ['id' => 16, 'name' => 'completed'],
            ['id' => 17, 'name' => 'up_coming'],
            ['id' => 18, 'name' => 'deny'],
        ]);
    }
}
