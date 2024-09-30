<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductConfig;

class UpdateConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProductConfig::create([
            'name' => 'SH/HR/C/FIX',
            'notes' => 'Select this type for General',
            'product_categories_id'=> 12
        ]);
    }
}
