<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('payment_options')->insert([
            ['id' => 1, 'name' => 'Cash'], 
            ['id' => 2, 'name' => 'Credit Card'],
            ['id' => 3, 'name' => 'Gcash'], 
            ['id' => 4, 'name' => 'Paymaya'],
        ]);
    }
}
