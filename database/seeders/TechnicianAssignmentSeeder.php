<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Franchise;
use App\Models\UserTechnician;
use Illuminate\Support\Facades\DB;

class TechnicianAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $franchises = Franchise::all();

        // Get all technicians
        $technicians = UserTechnician::all();

        // Shuffle technicians so assignments are random
        $technicians = $technicians->shuffle();

        // Assign franchise technicians
        foreach ($franchises as $franchise) {
            if ($technicians->isEmpty()) break;

            // Pick 1–2 technicians from the pool
            $assignedTechs = $technicians->splice(0, rand(1, 2))->pluck('id');
            $franchise->technicians()->sync($assignedTechs);
        }
    }
}
