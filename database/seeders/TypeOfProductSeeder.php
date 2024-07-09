<?php

namespace Database\Seeders;

use App\Models\TypeOfProduct;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeOfProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TypeOfProduct::create([
            'name' => 'Door',
            'notes' => 'Select this type for Door',
            
        ]);

        TypeOfProduct::create([
            'name' => 'Window',
            'notes' => 'Select this type for Window'
        ]);

        TypeOfProduct::create([
            'name' => 'Storefront',
            'notes' => 'Select this type for Storefront'
        ]);

        TypeOfProduct::create([
            'name' => 'Sidelite',
            'notes' => 'Select this type for Sidelite'
        ]);

        TypeOfProduct::create([
            'name' => 'Mullion',
            'notes' => 'Select this type for Mullion'
        ]);
    }
}
