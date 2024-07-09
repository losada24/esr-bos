<?php

namespace Database\Seeders;

use App\Models\ExtraWork;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enum\ExtraWorkUnit;

class ExtraWorkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ExtraWork::create([
            'name' => 'Cut Floor Single Door ',
            'notes' => 'Select this type for single door',
            'price'=> 130.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Cut floor double door',
            'notes' => 'Select this type for double door',
            'price'=> 180.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Casing single door',
            'notes' => 'Select this type for single door',
            'price'=> 180.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Casing double door',
            'notes' => 'Select this type for double door',
            'price'=> 230.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Casing double with sidelite',
            'notes' => 'Select this type for double with sidelite',
            'price'=> 250.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);
        
        ExtraWork::create([
            'name' => 'Remove burglar bar',
            'notes' => 'Select this type for Remove Burglar Bar',
            'price'=> 30.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Remove shutters and close hole',
            'notes' => 'Select this type for Remove shutters and close hole',
            'price'=> 30.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Remove glass block',
            'notes' => 'Select this type for Remove Glass Block ',
            'price'=> 150.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Difficult height remove glass block ',
            'notes' => 'Select this type for Difficult height remove glass block  ',
            'price'=> 200.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Make window opening ',
            'notes' => 'Demolition or adjustment of opening includes stucco and interior finish',
            'price'=> 0.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Make door opening ',
            'notes' => 'Demolition or adjustment of opening includes stucco and interior finish',
            'price'=> 0.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Close side opening of door and windows',
            'notes' => 'Demolition or adjustment of opening includes stucco and interior finish',
            'price'=> 0.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>true,
        ]);

        ExtraWork::create([
            'name' => 'Repair exterior window stucco',
            'notes' => 'Repair stucco',
            'price'=> 20.00,
            'unit'=> ExtraWorkUnit::SIDE->value,
            'planned'=>false,
        ]);

        ExtraWork::create([
            'name' => 'Repair exterior stucco of simple doors',
            'notes' => 'Repair stucco',
            'price'=> 40.00,
            'unit'=> ExtraWorkUnit::SIDE->value,
            'planned'=>false,
        ]);

        ExtraWork::create([
            'name' => 'Repair exterior stucco on double doors',
            'notes' => 'Repair stucco',
            'price'=> 40.00,
            'unit'=> ExtraWorkUnit::SIDE->value,
            'planned'=>false,
        ]);

        ExtraWork::create([
            'name' => 'Repair interior shirro of windows',
            'notes' => 'Repair shirro',
            'price'=> 25.00,
            'unit'=> ExtraWorkUnit::SIDE->value,
            'planned'=>false,
        ]);

        ExtraWork::create([
            'name' => 'Repair the interior drywall of the simple doors',
            'notes' => 'Repair drywall',
            'price'=> 50.00,
            'unit'=> ExtraWorkUnit::SIDE->value,
            'planned'=>false,
        ]);

        ExtraWork::create([
            'name' => 'Repair the interior drywall of the double doors',
            'notes' => 'Repair drywall',
            'price'=> 60.00,
            'unit'=> ExtraWorkUnit::SIDE->value,
            'planned'=>false,
        ]);

        ExtraWork::create([
            'name' => 'Repair the gap in the interior of the simple doors',
            'notes' => 'Repair gap',
            'price'=> 80.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>false,
        ]);

        ExtraWork::create([
            'name' => 'Repair the gap in the interior of the double doors',
            'notes' => 'Repair gap',
            'price'=> 120.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>false,
        ]);

        ExtraWork::create([
            'name' => 'Cut and repair stucco around windows with fin',
            'notes' => 'Repair stucco',
            'price'=> 100.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>false,
        ]);

        ExtraWork::create([
            'name' => 'Remove hollow metal doors and repair stucco',
            'notes' => 'Repair stucco',
            'price'=> 200.00,
            'unit'=> ExtraWorkUnit::EACH->value,
            'planned'=>false,
        ]);


    }
}
