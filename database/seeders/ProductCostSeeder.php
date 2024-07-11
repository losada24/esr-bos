<?php

namespace Database\Seeders;

use App\Models\ProductCost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductCost::create([
            'notes' => 'Retrofit, Door',
            'price' => 290.00,
            'type_of_work_id' => 1,
            'difficult_hight_price' => 0,
            'product_config_id' => 1,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Door',
            'price' => 360.00,
            'type_of_work_id' => 1,
            'product_config_id' => 2,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Door',
            'price' => 390.00,
            'type_of_work_id' => 1,
            'product_config_id' => 3,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Door',
            'price' => 360.00,
            'type_of_work_id' => 1,
            'product_config_id' => 4,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Door',
            'price' => 540.00,
            'type_of_work_id' => 1,
            'product_config_id' => 5,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Door',
            'price' => 700.00,
            'type_of_work_id' => 1,
            'product_config_id' => 6,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Door',
            'price' => 890.00,
            'type_of_work_id' => 1,
            'product_config_id' => 7,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Commercial Door',
            'price' => 320.00,
            'type_of_work_id' => 1,
            'product_config_id' => 8,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Commercial Door',
            'price' => 260.00,
            'type_of_work_id' => 1,
            'product_config_id' => 9,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Commercial Door',
            'price' => 450.00,
            'type_of_work_id' => 1,
            'product_config_id' => 10,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Commercial Door',
            'price' => 360.00,
            'type_of_work_id' => 1,
            'product_config_id' => 11,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Windows',
            'price' => 150.00,
            'type_of_work_id' => 1,
            'product_config_id' => 12,
        ]); 
        
        ProductCost::create([
            'notes' => 'Retrofit, Windows',
            'price' => 150.00,
            'type_of_work_id' => 1,
            'product_config_id' => 13,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Windows',
            'price' => 150.00,
            'type_of_work_id' => 1,
            'product_config_id' => 14,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Windows',
            'price' => 180.00,
            'type_of_work_id' => 1,
            'product_config_id' => 15,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Windows',
            'price' => 150.00,
            'type_of_work_id' => 1,
            'product_config_id' => 16,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Windows',
            'price' => 150.00,
            'type_of_work_id' => 1,
            'product_config_id' => 17,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Windows',
            'price' => 150.00,
            'type_of_work_id' => 1,
            'product_config_id' => 18,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Sidelite',
            'price' => 145.00,
            'type_of_work_id' => 1,
            'product_config_id' => 19,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Storefront',
            'price' => 8.00,
            'type_of_work_id' => 1,
            'product_config_id' => 20,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Mullion',
            'price' => 30.00,
            'type_of_work_id' => 1,
            'product_config_id' => 21,
        ]);

        ProductCost::create([
            'notes' => 'Retrofit, Mullion',
            'price' => 30.00,
            'type_of_work_id' => 1,
            'product_config_id' => 22,
        ]);
        
        ProductCost::create([
            'notes' => 'Retrofit, Mullion',
            'price' => 30.00,
            'type_of_work_id' => 1,
            'product_config_id' => 23,
        ]);

    }
}
