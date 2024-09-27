<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCost;

class AddPriceProductCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductCost::create([
            'notes' => 'Retrofit, Windows',
            'price' => 150.00,
            'difficult_hight_price'=> 30.00,
            'type_of_work_id' => 1,
            'product_config_id' => 26,
        ]);

        ProductCost::create([
            'notes' => 'New Constrution with wood, Windows',
            'price' => 120.00,
            'difficult_hight_price'=> 30.00,
            'type_of_work_id' => 2,
            'product_config_id' => 26,
        ]);

        ProductCost::create([
            'notes' => 'New Constrution without wood, Windows',
            'price' => 80.00,
            'difficult_hight_price'=> 0.00,
            'type_of_work_id' => 3,
            'product_config_id' => 26,
        ]);

        
    }
}
