<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;

class AddPivotCategorySeeder extends Seeder
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
    }
}
