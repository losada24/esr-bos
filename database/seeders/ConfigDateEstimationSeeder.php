<?php

namespace Database\Seeders;

use App\Models\ConfigDateEstimation;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfigDateEstimationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConfigDateEstimation::create([
            'travel_cost_id' => 1,
            'type_of_housing_id' => 1, // Single Family Home
            'weeks' => 8,
            'week_day' => Carbon::MONDAY,
            'days_difference_between_services' => 1,
        ]);

        ConfigDateEstimation::create([
            'travel_cost_id' => 1,
            'type_of_housing_id' => 2, // Appartment
            'weeks' => 8,
            'week_day' => Carbon::WEDNESDAY,
            'days_difference_between_services' => 1,
        ]);

        ConfigDateEstimation::create([
            'travel_cost_id' => 3, //Charlotte, Glades, Hendry, Lee &  Martin
            'type_of_housing_id' => 1, // Single Family Home
            'weeks' => 8,
            'week_day' => Carbon::WEDNESDAY,
            'days_difference_between_services' => 1,
        ]);
    }
}
