<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startDate = Carbon::now()->subMonth();

        DB::table('branches')->insert([
            [
                'id' => 1,
                'franchise_id' => 1,
                'status_id' => 1,
                'name' => 'jp branch',
                'email' => 'jp@gmail.com',
                'phone' => '9123123123',
                'address' => '123 Sto Rosario',
                'region' => 'Region 3',
                'province' => 'Pampanga',
                'city' => 'City of Angeles',
                'barangay' => 'Sto Domingo',
                'postal_code' => '2009',
                'dti_registration_attachment' => '123.jpg',
                'mayor_permit_attachment' => '123.jpg',
                'proof_agreement_attachment' => '123.jpg',
                'created_at' => $startDate,
                'updated_at' => $startDate
            ],
        ]);
    }
}
