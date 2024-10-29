<?php

namespace Database\Seeders;
use App\Models\ProductConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddPivotConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      ProductConfig::create([
        'name' => 'Pivot Door',
        'notes' => 'Select this type for Pivot Door',
        'product_categories_id'=> 13
    ]);

    }
}
