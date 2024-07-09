<?php

namespace Database\Seeders;

use App\Models\DurationOfWork;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DurationOfWorkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DurationOfWork::create([
            'name' => '1-6 units (1 Day)',
            'notes' => '1-6 units (Windows and Doors) 1 Day ',
            'price'=> 0.00,
    
        ]);

        DurationOfWork::create([
            'name' => '7-15 units (2 Day)',
            'notes' => '7-15 units (Windows and Doors) 2 Day',
            'price'=> 900.00,
    
        ]);

        DurationOfWork::create([
            'name' => '16-25 units (3 Day)',
            'notes' => '16-25 units (Windows and Doors) 3 Day',
            'price'=> 1300.00,
    
        ]);

        DurationOfWork::create([
            'name' => '26-35 units (4 Day)',
            'notes' => '26-35 units (Windows and Doors ) 4 Day',
            'price'=> 1650.00,
    
        ]);

        DurationOfWork::create([
            'name' => '36-46 units (5 Day)',
            'notes' => '36-46 units (Windows and Doors ) 5 Day',
            'price'=> 2000.00,
    
        ]);

        DurationOfWork::create([
            'name' => 'More 47 units  (6 Day)',
            'notes' => 'More 47 units (Windows and Doors ) 6 Day',
            'price'=> 2400.00,
    
        ]);
    }
}
