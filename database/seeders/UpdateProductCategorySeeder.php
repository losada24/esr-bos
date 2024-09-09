<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      DB::table('product_categories')->where('id','10')->update(['name'=>'Storefront']);
      DB::table('product_categories')->where('id','11')->update(['name'=>'Sidelite']);
      DB::table('product_configs')->where('id','19')->update(['name'=>'Sidelite']);
      DB::table('product_configs')->where('id','20')->update(['name'=>'Storefront']);
    }
}
