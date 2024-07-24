<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateProductCostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      DB::table('product_costs')->where('id','12')->update(['difficult_hight_price'=>'30.00']);
      DB::table('product_costs')->where('id','13')->update(['difficult_hight_price'=>'30.00']);
      DB::table('product_costs')->where('id','14')->update(['difficult_hight_price'=>'30.00']);
      DB::table('product_costs')->where('id','15')->update(['difficult_hight_price'=>'20.00']);
      DB::table('product_costs')->where('id','16')->update(['difficult_hight_price'=>'30.00']);
      DB::table('product_costs')->where('id','17')->update(['difficult_hight_price'=>'30.00']);
      DB::table('product_costs')->where('id','18')->update(['difficult_hight_price'=>'30.00']);
    }
}
