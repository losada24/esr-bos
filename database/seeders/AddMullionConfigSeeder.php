<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductConfig;

class AddMullionConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductConfig::create([
            'name' => '45 Bay Mullion',
            'notes' => 'Select this type for Mullion',
            'product_categories_id'=> 9
        ]);
    }
}
