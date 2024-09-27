<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductConfig;

class AddConfigShVenturaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductConfig::create([
            'name' => 'OX(AV)',
            'notes' => 'Select this type for General',
            'product_categories_id'=> 5
        ]);
    }
}
