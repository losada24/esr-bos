<?php

namespace Database\Seeders;

use App\Enum\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AddRolePlantManager extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plant_manager = Role::create(['name' => RoleEnum::$PLANT_MANAGER]);
    }
}
