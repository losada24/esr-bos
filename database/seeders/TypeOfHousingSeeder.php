<?php

namespace Database\Seeders;

use App\Models\TypeOfHousing;
use Illuminate\Database\Seeder;

class TypeOfHousingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeOfHousing::create([
            'name' => 'Single Family Home',
            'notes' => 'Select this type for Single Family Home'
        ]);

        TypeOfHousing::create([
            'name' => 'Apartment',
            'notes' => 'Select this type for Apartment'
        ]);

        TypeOfHousing::create([
            'name' => 'Commercial',
            'notes' => 'Select this type for Commercial'
        ]);
    }
}
