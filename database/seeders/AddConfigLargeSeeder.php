<?php

namespace Database\Seeders;

use App\Models\ProductConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddConfigLargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      ProductConfig::create([
        'name' => 'Large',
        'notes' => 'Select this type for General',
        'product_categories_id'=> 12
    ]);
    }
}
