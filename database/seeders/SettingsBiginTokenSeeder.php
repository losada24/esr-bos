<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsBiginTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::create([
            'name' => 'BIGIN_TOKEN',
            'label' => 'Bigin Token',
            'value' => '',
        ]);

        Setting::create([
          'name' => 'BIGIN_REFRESH_TOKEN',
          'label' => 'Bigin Refresh Token',
          'value' => '',
        ]);

        Setting::create([
          'name' => 'BIGIN_TOKEN_EXPIRES_IN',
          'label' => 'Bigin Token Expiration',
          'value' => '',
        ]);
    }
}
