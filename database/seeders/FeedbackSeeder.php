<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('feedback')->insert([
            [
                'name' => 'Maria Santos',
                'user_type_id' => 6, 
                'rating' => 5.0,
                'description' => 'Safe at maayos yung biyahe ko gamit ang e-taxi nila. Real-time ko nakikita kung nasaan yung driver at magkano ang pamasahe bago pa ako sumakay. Madali rin magbayad gamit ang GCash! Sobrang convenient lalo na pag rush hour.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Juan Dela Cruz',
                'user_type_id' => 6, 
                'rating' => 4.5,
                'description' => 'Maganda ang serbisyo at maayos kausap ang driver. Konting improvement lang sa waiting time pero overall satisfied ako sa experience.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ana Reyes',
                'user_type_id' => 6, 
                'rating' => 4.0,
                'description' => 'Malinis ang sasakyan at polite ang driver. Madaling gamitin ang app kahit first time user.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
