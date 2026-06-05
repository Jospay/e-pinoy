<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Franchise;
use App\Models\Branch;
use App\Models\Status;
use App\Models\VehicleType;
use App\Models\TricycleTerminal;
use Illuminate\Database\Seeder;

class TricycleTerminalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $franchise = Franchise::whereHas('vehicleTypes', function ($query) {
            $query->where('name', 'tricycle');
        })->first();

        if (!$franchise) {
            $this->command->error('No Franchise supports the "tricycle" vehicle type. Seed franchises with tricycle vehicle types first.');
            return;
        }

        $dummyTerminals = [
            [
                'name' => 'Central Poblacion Tricycle Hub',
                'region' => 'Region III',
                'province' => 'Pampanga',
                'city' => 'Angeles City',
                'barangay' => 'Baluarte',
                'street' => 'McArthur Highway',
                'postal_code' => '2009',
                'latitude' => 15.1451,
                'longitude' => 120.5941,
            ],
            [
                'name' => 'San Nicolas Market Terminal',
                'region' => 'Region III',
                'province' => 'Pampanga',
                'city' => 'Angeles City',
                'barangay' => 'San Nicolas',
                'street' => 'Miranda St',
                'postal_code' => '2009',
                'latitude' => 15.1344,
                'longitude' => 120.5902,
            ]
        ];

        foreach ($dummyTerminals as $terminalData) {
            TricycleTerminal::create(array_merge($terminalData, [
                'franchise_id' => $franchise->id,
                'status_id'    => 1
            ]));
        }
    }
}
