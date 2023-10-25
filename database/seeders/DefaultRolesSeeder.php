<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Enum\RoleEnum;

class DefaultRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role_admin = Role::create(['name' => RoleEnum::$ADMIN]);
        $role_client_admin = Role::create(['name' => RoleEnum::$CLIENT_ADMIN]);
        $role_client = Role::create(['name' => RoleEnum::$CLIENT]);
    }
}
