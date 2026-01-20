<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Maintenance;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $inventories = Inventory::all();
        if($inventories->isEmpty()) return;

        // =================================================================
        // SCENARIO 1: CURRENT MAINTENANCE (Active)
        // Vehicle Status: 5 (Maintenance)
        // Maintenance Status: 1 (Active)
        // Expense Status: 6 (Pending)
        // =================================================================
        
        $vehiclesInMaintenance = Vehicle::where('status_id', 5)->get();

        foreach ($vehiclesInMaintenance as $vehicle) {
            $inventory = $inventories->random();
            $cost = $inventory->unit_price + rand(500, 2000); // Parts + Labor

            // DATA INTEGRITY: Find a technician assigned to THIS vehicle's Franchise
            $technicianId = $this->findTechnicianForVehicle($vehicle);

            if (!$technicianId) continue; // Skip if no tech found (shouldn't happen with correct seeding)

            // 1. Create Maintenance
            $maintenance = Maintenance::create([
                'vehicle_id'            => $vehicle->id,
                'inventory_id'          => $inventory->id,
                'technician_id'         => $technicianId,
                'status_id'             => 1, // Active Maintenance
                'description'           => 'Urgent repair: ' . $inventory->name,
                'maintenance_date'      => Carbon::now(),
                'next_maintenance_date' => Carbon::now()->addMonths(3),
            ]);

            // 2. Create Pending Expense
            Expense::create([
                'status_id'         => 6, // Pending
                'franchise_id'      => $vehicle->franchise_id,
                'maintenance_id'    => $maintenance->id,
                'payment_option_id' => 1,
                'invoice_no'        => 'MNT-' . strtoupper(Str::random(8)),
                'amount'            => $cost,
                'currency'          => 'PHP',
                'payment_date'      => null,
                'notes'             => 'Pending payment for active maintenance',
            ]);
        }

        // =================================================================
        // SCENARIO 2: COMPLETED HISTORY
        // Vehicle Status: 1 (Active)
        // Maintenance Status: 16 (Completed)
        // Expense Status: 8 (Paid)
        // =================================================================

        // Pick 20 random active vehicles to give history
        $activeVehicles = Vehicle::where('status_id', 1)->inRandomOrder()->limit(20)->get();

        foreach ($activeVehicles as $vehicle) {
            $inventory = $inventories->random();
            $cost = $inventory->unit_price + rand(500, 2000);
            $pastDate = Carbon::now()->subDays(rand(5, 30))
                ->setHour(rand(0, 23))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));

            // DATA INTEGRITY: Find a technician assigned to THIS vehicle's Franchise or
            $technicianId = $this->findTechnicianForVehicle($vehicle);

            if (!$technicianId) continue;

            // 1. Create Completed Maintenance
            $maintenance = Maintenance::create([
                'vehicle_id'            => $vehicle->id,
                'inventory_id'          => $inventory->id,
                'technician_id'         => $technicianId,
                'status_id'             => 16, // Completed
                'description'           => 'Routine maintenance: ' . $inventory->name,
                'maintenance_date'      => $pastDate,
                'next_maintenance_date' => $pastDate->copy()->addMonths(3),
            ]);

            // 2. Create Paid Expense
            Expense::create([
                'status_id'         => 8, // Paid
                'franchise_id'      => $vehicle->franchise_id,
                'maintenance_id'    => $maintenance->id,
                'payment_option_id' => rand(1, 4),
                'invoice_no'        => 'MNT-PAID-' . strtoupper(Str::random(8)),
                'amount'            => $cost,
                'currency'          => 'PHP',
                'payment_date'      => $pastDate,
                'notes'             => 'Paid in full',
                'created_at'        => $pastDate,
                'updated_at'        => $pastDate,
            ]);
        }
    }

    /**
     * Helper to enforce Data Integrity
     */
    private function findTechnicianForVehicle($vehicle)
    {
        if ($vehicle->franchise_id) {
            return DB::table('franchise_user_technician')
                ->where('franchise_id', $vehicle->franchise_id)
                ->inRandomOrder()
                ->value('user_technician_id');
        }
        return null;
    }
}