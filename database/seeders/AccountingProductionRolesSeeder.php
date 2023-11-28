<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Enum\RoleEnum;

class AccountingProductionRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      $role_accounting = Role::create(['name' => RoleEnum::$ACCOUNTING]);
      $role_production = Role::create(['name' => RoleEnum::$PRODUCTION]);
    }
}
