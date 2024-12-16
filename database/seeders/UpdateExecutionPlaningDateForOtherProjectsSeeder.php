<?php

namespace Database\Seeders;

use App\Enum\PlaningDateSupervisorEnum;
use App\Enum\ServiceEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateExecutionPlaningDateForOtherProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {$projectsWithPermissionsValue = PlaningDateSupervisorEnum::PROJECTS_WITH_PERMISSIONS->value;
      $projectsWithoutPermissionsValue = PlaningDateSupervisorEnum::PROJECTS_WITHOUT_PERMISSIONS->value;

      // Condición 1: Actualiza los registros donde type_of_housing_id = 1 o 2 y city_permits = 1 y el servicio es INSTALLATION o INSTALLATION_ONLY
      DB::table('orders')
          ->whereIn('type_of_housing_id', [1, 2])
          ->where('city_permits', 1)
          ->whereIn('service', [
              ServiceEnum::INSTALLATION->value,
              ServiceEnum::INSTALLATION_ONLY->value
          ])
          ->update([
              'execution_planing_date' => $projectsWithPermissionsValue,
          ]);

      // Condición 2: Actualiza los registros donde type_of_housing_id = 1 o 2 y city_permits = 0 y el servicio es INSTALLATION o INSTALLATION_ONLY
      DB::table('orders')
          ->whereIn('type_of_housing_id', [1, 2])
          ->where('city_permits', 0)
          ->whereIn('service', [
              ServiceEnum::INSTALLATION->value,
              ServiceEnum::INSTALLATION_ONLY->value
          ])
          ->update([
              'execution_planing_date' => $projectsWithoutPermissionsValue,
          ]);


    }
}
