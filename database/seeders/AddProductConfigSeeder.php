<?php

namespace Database\Seeders;
use App\Models\ProductConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddProductConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      ProductConfig::create([
        'name' => 'O (Large)',
        'notes' => 'Select this type for Fix',
        'product_categories_id'=> 7
    ]);

      ProductConfig::create([
        'name' => 'XOX (96")',
        'notes' => 'Select this type for Horizontal Roller',
        'product_categories_id'=> 5
    ]);

    }
}
