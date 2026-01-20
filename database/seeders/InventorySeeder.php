<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\Franchise;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'Synthetic Motor Oil 5W-30', 'category' => 'Consumables', 'price' => 800],
            ['name' => 'Ceramic Brake Pads', 'category' => 'Mechanical', 'price' => 2500],
            ['name' => 'Heavy Duty Battery', 'category' => 'Electrical', 'price' => 6000],
            ['name' => 'All-Terrain Tire', 'category' => 'Mechanical', 'price' => 4500],
            ['name' => 'Air Filter Replacement', 'category' => 'Consumables', 'price' => 450],
        ];

        foreach ($items as $index => $item) {
            Inventory::create([
                'franchise_id' => Franchise::inRandomOrder()->value('id'),
                'code_no'       => 'INV-000' . ($index + 1),
                'name'          => $item['name'],
                'category'      => $item['category'],
                'specification' => 'Standard OEM Specification',
                'quantity'      => 50,
                'unit_price'    => $item['price'],
                'notes'         => 'Initial Stock',
            ]);
        }
    }
}
