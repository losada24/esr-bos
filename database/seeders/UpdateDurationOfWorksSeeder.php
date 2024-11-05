<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateDurationOfWorksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      DB::table('duration_of_works')->where('id','1')->update(['number_of_day'=> '1']);
      DB::table('duration_of_works')->where('id','2')->update(['number_of_day'=>'2']);
      DB::table('duration_of_works')->where('id','3')->update(['number_of_day'=>'3']);
      DB::table('duration_of_works')->where('id','4')->update(['number_of_day'=>'4']);
      DB::table('duration_of_works')->where('id','5')->update(['number_of_day'=>'5']);
      DB::table('duration_of_works')->where('id','6')->update(['number_of_day'=>'6']);
    }
}
