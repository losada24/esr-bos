<?php

namespace Database\Seeders;

use App\Enum\ExtraWorkUnit;
use App\Models\ExtraWork;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddExtraWorkCutFloorTripleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      ExtraWork::create([
        'name' => 'Cut floor triple door',
        'notes' => 'Select this type for door',
        'price'=> 230.00,
        'unit'=> ExtraWorkUnit::EACH->value,
        'planned'=>true,
    ]);
    }
}
