<?php

namespace Database\Seeders;

use App\Models\TravelCost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TravelCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TravelCost::create([
            'name' => 'Miami-Dade & Broward County',
            'notes' => 'Miami-Dade & Broward County',
            'price'=> 0.00
        ]);

        TravelCost::create([
            'name' => 'Collier, Monroe & Palm Beach County',
            'notes' => 'Collier, Monroe & Palm Beach County',
            'price'=> 150.00,
        ]);
        
        TravelCost::create([
            'name' => 'Charlotte, Glades, Hendry, Lee &  Martin',
            'notes' => 'Charlotte, Glades, Hendry, Lee &  Martin',
            'price'=> 250.00,
        ]);

        TravelCost::create([
            'name' => 'Okeechobee, St. Lucie, Sarasota,Highlands & DeSoto',
            'notes' => 'Okeechobee, St. Lucie, Sarasota,Highlands & DeSoto',
            'price'=> 350.00,
        ]);

        TravelCost::create([
            'name' => 'Hardee, Manatee & Indian River',
            'notes' => 'Hardee, Manatee & Indian River',
            'price'=> 450.00,
        ]);

        TravelCost::create([
            'name' => 'Brevard, Hillsborough, Osceola, Pinellas & Polk',
            'notes' => 'Brevard, Hillsborough, Osceola, Pinellas & Polk',
            'price'=> 550.00,
        ]);

        TravelCost::create([
            'name' => 'Pasco, Seminole, Sumter, Orange, Hernando & Lake',
            'notes' => 'Pasco, Seminole, Sumter, Orange, Hernando & Lake',
            'price'=> 650.00,
        ]);
    }
}
