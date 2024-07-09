<?php

namespace Database\Seeders;

use App\Models\ProductConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductConfig::create([
            'name' => 'X',
            'notes' => 'Select this type for French Door',
            'product_categories_id'=> 1
        ]);

        ProductConfig::create([
            'name' => 'XX (High 85")',
            'notes' => 'Select this type for French Door',
            'product_categories_id'=> 1
        ]);

        ProductConfig::create([
            'name' => 'XX (High +86")',
            'notes' => 'Select this type for French Door',
            'product_categories_id'=> 1
        ]);

        ProductConfig::create([
            'name' => 'XX',
            'notes' => 'Select this type for Sliding Door',
            'product_categories_id'=> 2
        ]);

        ProductConfig::create([
            'name' => 'XXX',
            'notes' => 'Select this type for Sliding Door',
            'product_categories_id'=> 2
        ]);

        ProductConfig::create([
            'name' => 'XXXX',
            'notes' => 'Select this type for Sliding Door',
            'product_categories_id'=> 2
        ]);

        ProductConfig::create([
            'name' => 'XXXXXX',
            'notes' => 'Select this type for Sliding Door',
            'product_categories_id'=> 2
        ]);

        ProductConfig::create([
            'name' => 'W/Trans',
            'notes' => 'Select this type for Sliding Door',
            'product_categories_id'=> 3
        ]);

        ProductConfig::create([
            'name' => 'N/A',
            'notes' => 'Select this type for Sliding Door',
            'product_categories_id'=> 3
        ]);

        ProductConfig::create([
            'name' => 'W/Trans',
            'notes' => 'Select this type for Sliding Door',
            'product_categories_id'=> 4
        ]);

        ProductConfig::create([
            'name' => 'N/A',
            'notes' => 'Select this type for Sliding Door',
            'product_categories_id'=> 4
        ]);

        ProductConfig::create([
            'name' => 'OX',
            'notes' => 'Select this type for Single Hung',
            'product_categories_id'=> 5
        ]);

        ProductConfig::create([
            'name' => 'XO',
            'notes' => 'Select this type for Horizontal Roller',
            'product_categories_id'=> 6
        ]);

        ProductConfig::create([
            'name' => 'OX',
            'notes' => 'Select this type for Horizontal Roller',
            'product_categories_id'=> 6
        ]);

        ProductConfig::create([
            'name' => 'XOX',
            'notes' => 'Select this type for Horizontal Roller',
            'product_categories_id'=> 6
        ]);

        ProductConfig::create([
            'name' => 'O',
            'notes' => 'Select this type for Fix',
            'product_categories_id'=> 7
        ]);

        ProductConfig::create([
            'name' => 'X',
            'notes' => 'Select this type for Casement',
            'product_categories_id'=> 8
        ]);

        ProductConfig::create([
            'name' => 'XX',
            'notes' => 'Select this type for Casement',
            'product_categories_id'=> 8
        ]);

        ProductConfig::create([
            'name' => 'N/A',
            'notes' => 'Select this type for Sidelite',
            'product_categories_id'=> 11
        ]);

        ProductConfig::create([
            'name' => 'N/A',
            'notes' => 'Select this type for Storefront',
            'product_categories_id'=> 10
        ]);

        ProductConfig::create([
            'name' => 'Mullion 1X3',
            'notes' => 'Select this type for Mullion',
            'product_categories_id'=> 9
        ]);

        ProductConfig::create([
            'name' => 'Mullion 1X4',
            'notes' => 'Select this type for Mullion',
            'product_categories_id'=> 9
        ]);

        ProductConfig::create([
            'name' => 'Mullion 2X6',
            'notes' => 'Select this type for Mullion',
            'product_categories_id'=> 9
        ]);

    }
}
