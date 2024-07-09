<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductCategory::create([
            'name' => 'French Door',
            'notes' => 'Select this type for Door',
            'type_of_products_id'=> 1
        ]);

        ProductCategory::create([
            'name' => 'Sliding Door',
            'notes' => 'Select this type for Door',
            'type_of_products_id'=> 1
        ]);

        ProductCategory::create([
            'name' => 'Commercial Door Single',
            'notes' => 'Select this type for Door',
            'type_of_products_id'=> 1
        ]);

        ProductCategory::create([
            'name' => 'Commercial Door Double',
            'notes' => 'Select this type for Door',
            'type_of_products_id'=> 1
        ]);

        ProductCategory::create([
            'name' => 'Single Hung',
            'notes' => 'Select this type for Window',
            'type_of_products_id'=> 2
        ]);

        ProductCategory::create([
            'name' => 'Horizontal Roller',
            'notes' => 'Select this type for Window',
            'type_of_products_id'=> 2
        ]);

        ProductCategory::create([
            'name' => 'Fix',
            'notes' => 'Select this type for Window',
            'type_of_products_id'=> 2
        ]);

        ProductCategory::create([
            'name' => 'Casement',
            'notes' => 'Select this type for Window',
            'type_of_products_id'=> 2
        ]);

        ProductCategory::create([
            'name' => 'Mullion',
            'notes' => 'Select this type for Mullion',
            'type_of_products_id'=> 5
        ]);

        ProductCategory::create([
            'name' => 'N/A',
            'notes' => 'Select this type for Storefront',
            'type_of_products_id'=> 3
        ]);

        ProductCategory::create([
            'name' => 'N/A',
            'notes' => 'Select this type for Sdelite',
            'type_of_products_id'=> 4
        ]);
    }
}
