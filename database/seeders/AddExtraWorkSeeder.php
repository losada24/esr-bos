<?php

namespace Database\Seeders;

use App\Enum\ExtraWorkUnit;
use App\Models\ExtraWork;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddExtraWorkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      ExtraWork::create([
        'name' => 'Casing window',
        'notes' => 'Select this type for window',
        'price'=> 180.00,
        'unit'=> ExtraWorkUnit::EACH->value,
        'planned'=>true,
    ]);
    }
}
