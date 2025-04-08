<?php

namespace Database\Seeders;

use App\Models\TypeOfProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddExtraWorkTypeOfProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      $typeOfProductsWindow = TypeOfProduct::where('id', 1)->first();
      $typeOfProductsWindow->extraWorks()->attach([24]);
    }
}
