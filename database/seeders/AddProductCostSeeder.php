<?php

namespace Database\Seeders;
use App\Models\ProductCost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddProductCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
      ProductCost::create([
        'notes' => 'Retrofit, Window',
        'price' => 180.00,
        'difficult_hight_price'=> 20.00,
        'type_of_work_id' => 1,
        'product_config_id' => 24,
    ]);

    ProductCost::create([
        'notes' => 'Retrofit, Window',
        'price' => 300.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 1,
        'product_config_id' => 25,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Door',
        'price' => 240.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 1,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Door',
        'price' => 300.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 2,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Door',
        'price' => 330.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 3,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Door',
        'price' => 300.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 4,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Door',
        'price' => 450.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 5,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Door',
        'price' => 580.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 6,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Commercial Door Single',
        'price' => 220.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 9,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Commercial Door Single',
        'price' => 280.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 8,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Commercial Door Double',
        'price' => 300.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 11,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Commercial Door Double',
        'price' => 380.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 10,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Window Single Hung',
        'price' => 120.00,
        'difficult_hight_price'=> 30.00,
        'type_of_work_id' => 2,
        'product_config_id' => 12,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Window Horizontal Roller',
        'price' => 120.00,
        'difficult_hight_price'=> 30.00,
        'type_of_work_id' => 2,
        'product_config_id' => 13,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Window Horizontal Roller',
        'price' => 120.00,
        'difficult_hight_price'=> 30.00,
        'type_of_work_id' => 2,
        'product_config_id' => 14,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Window Horizontal Roller',
        'price' => 150.00,
        'difficult_hight_price'=> 30.00,
        'type_of_work_id' => 2,
        'product_config_id' => 15,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Window Fixed',
        'price' => 120.00,
        'difficult_hight_price'=> 30.00,
        'type_of_work_id' => 2,
        'product_config_id' => 16,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Window Casement',
        'price' => 120.00,
        'difficult_hight_price'=> 30.00,
        'type_of_work_id' => 2,
        'product_config_id' => 17,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Window Casement',
        'price' => 120.00,
        'difficult_hight_price'=> 30.00,
        'type_of_work_id' => 2,
        'product_config_id' => 18,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Window',
        'price' => 150.00,
        'difficult_hight_price'=> 30.00,
        'type_of_work_id' => 2,
        'product_config_id' => 24,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Window',
        'price' => 300.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 25,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Sidelite',
        'price' => 120.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 19,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Storefront',
        'price' => 7.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 20,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Mullion',
        'price' => 25.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 21,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Mullion',
        'price' => 25.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 22,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution with wood, Mullion',
        'price' => 25.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 2,
        'product_config_id' => 23,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Door',
        'price' => 150.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 1,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Door',
        'price' => 180.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 2,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Door',
        'price' => 200.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 3,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Door',
        'price' => 250.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 4,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Door',
        'price' => 380.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 5,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Door',
        'price' => 470.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 6,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Door',
        'price' => 700.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 7,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Commercial Door Single',
        'price' => 200.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 9,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Commercial Door Single',
        'price' => 250.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 8,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Commercial Door Double',
        'price' => 270.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 11,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Commercial Door Double',
        'price' => 350.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 10,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Window Single Hung',
        'price' => 80.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 12,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Window Horizontal Roller',
        'price' => 80.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 13,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Window Horizontal Roller',
        'price' => 80.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 14,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Window Horizontal Roller',
        'price' => 100.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 15,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Window Fixed',
        'price' => 80.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 16,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Window Casement',
        'price' => 80.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 17,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Window Casement',
        'price' => 80.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 18,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Window',
        'price' => 100.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 24,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Window',
        'price' => 300.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 25,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Sidelite',
        'price' => 100.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 19,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Storefront',
        'price' => 7.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 20,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Mullion',
        'price' => 20.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 21,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Mullion',
        'price' => 20.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 22,
    ]);

    ProductCost::create([
        'notes' => 'New Constrution without wood, Mullion',
        'price' => 20.00,
        'difficult_hight_price'=> 0.00,
        'type_of_work_id' => 3,
        'product_config_id' => 23,
    ]);


    }
}
