<?php

namespace Database\Seeders;

use App\Enum\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UpdateRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client_admin = Role::where('name', RoleEnum::$CLIENT_ADMIN)->first();
        $client_admin->update(['name' => RoleEnum::$DEALER]);

        $client = Role::where('name', RoleEnum::$CLIENT)->first();
        $client->update(['name' => RoleEnum::$SUB_DEALER]);

        $account_manager = Role::create(['name' => RoleEnum::$ACCOUNT_MANAGER]);
    }
}
