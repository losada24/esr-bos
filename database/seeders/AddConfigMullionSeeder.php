<?php

namespace Database\Seeders;
use App\Models\ProductCost;
use App\Models\ProductConfig;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddConfigMullionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      ProductConfig::create([
        'name' => 'Mullion',
        'notes' => 'Select this type for Mullion',
        'product_categories_id'=> 9
      ]);

      ProductCost::create([
        'notes' => 'Retrofit, Mullion',
        'price' => 30.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 1,
        'product_config_id' => 31,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Mullion',
        'price' => 25.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 31,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Mullion',
        'price' => 20.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 31,
    ]);
    }
}
