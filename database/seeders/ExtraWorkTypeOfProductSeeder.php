<?php

namespace Database\Seeders;

use App\Models\TypeOfProduct;
use App\Models\TypeOfWork;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExtraWorkTypeOfProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typeOfProductsDoor = TypeOfProduct::where('id', 1)->first();
        $typeOfProductsDoor->extraWorks()->attach([1, 2, 3, 4, 5,6,7,8,9 ]);
        $typeOfProductsWindow = TypeOfProduct::where('id', 2)->first();
        $typeOfProductsWindow->extraWorks()->attach([6,7,8,9,23 ]);
    }
}
