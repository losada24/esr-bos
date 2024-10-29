<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\ProductConfig;
use App\Models\ProductCost;

class AddPivotProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      ProductCategory::create([
        'name' => 'Pivot Door',
        'notes' => 'Select this type for Door',
        'type_of_products_id'=> 1
      ]);

      ProductConfig::create([
        'name' => 'Pivot Door',
        'notes' => 'Select this type for Pivot Door',
        'product_categories_id'=> 13
      ]);

      ProductCost::create([
          'notes' => 'Retrofit, Windows',
          'price' => 0.00,
          'difficult_hight_price'=> 0.00,
          'type_of_work_id' => 1,
          'product_config_id' => 30,
      ]);

      ProductCost::create([
          'notes' => 'New Constrution with wood, Windows',
          'price' => 0.00,
          'difficult_hight_price'=> 0.00,
          'type_of_work_id' => 2,
          'product_config_id' => 30,
      ]);

      ProductCost::create([
          'notes' => 'New Constrution without wood, Windows',
          'price' => 0.00,
          'difficult_hight_price'=> 0.00,
          'type_of_work_id' => 3,
          'product_config_id' => 30,
      ]);
    }
}
