<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Enum\RoleEnum;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      /*Role::create(['name' => RoleEnum::ADMIN->value]);
      Role::create(['name' => RoleEnum::ACCOUNT_MANAGER->value]);
      Role::create(['name' => RoleEnum::INSTALLER->value]);
      Role::create(['name' => RoleEnum::SUPERVISOR->value]);
      Role::create(['name' => RoleEnum::OWNER->value]);*/
      Role::create(['name' => RoleEnum::SERVICE_MANAGER->value]);
      Role::create(['name' => RoleEnum::WAREHOUSE_MANAGER->value]);
    }
}
