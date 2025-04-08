<?php

namespace Database\Seeders;

use App\Models\ProductCost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddProductCostLargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      ProductCost::create([
        'notes' => 'Retrofit, Windows',
        'price' => 180.00,
        'difficult_hight_price'=> 20.00,
        'type_of_work_id' => 1,
        'product_config_id' => 32,
    ]);

        ProductCost::create([
          'notes' => 'New Constrution with wood, Windows',
          'price' => 150.00,
          'difficult_hight_price'=> 30.00,
          'type_of_work_id' => 2,
          'product_config_id' => 32,
      ]);

      ProductCost::create([
        'notes' => 'New Constrution without wood, Windows',
        'price' => 100.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 32,
    ]);
    }
}
