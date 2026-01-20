<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PercentageTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('percentage_types')->insert([
            [
                'name'       => 'tax',
                'type'       => 'Percentage',
                'value'      => 1,
            ],
            [
                'name'       => 'bank',
                'type'       => 'Percentage',
                'value'      => 1,
            ],
            [
                'name'       => 'markup_fee',
                'type'       => 'PHP',
                'value'      => 10,
            ],
            [
                'name'       => 'system_fee',
                'type'       => 'PHP',
                'value'      => 10,
            ],
        ]);
    }
}
