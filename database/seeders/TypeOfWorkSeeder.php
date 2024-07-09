<?php

namespace Database\Seeders;

use App\Models\TypeOfWork;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeOfWorkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeOfWork::create([
            'name' => 'Retrofit',
            'notes' => 'Select this type for retrofit works'
        ]);

        TypeOfWork::create([
            'name' => 'New Contruction with wood',
            'notes' => ''
        ]);

        TypeOfWork::create([
            'name' => 'New Contruction without wood',
            'notes' => ''
        ]);
    }
}
