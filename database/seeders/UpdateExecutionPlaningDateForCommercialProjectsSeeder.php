<?php

namespace Database\Seeders;

use App\Enum\PlaningDateSupervisorEnum;
use App\Enum\ServiceEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateExecutionPlaningDateForCommercialProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      // Obtén el valor del enum para los proyectos comerciales
      $commercialProjectsValue = PlaningDateSupervisorEnum::COMMERCIAL_PROJECTS->value;

      // Actualiza los registros donde type_of_housing_id = 3 y el servicio es INSTALLATION o INSTALLATION_ONLY
      DB::table('orders')
          ->where('type_of_housing_id', 3)
          ->whereIn('service', [
              ServiceEnum::INSTALLATION->value,
              ServiceEnum::INSTALLATION_ONLY->value
          ])
          ->update([
              'execution_planing_date' => $commercialProjectsValue,
          ]);

    }
}
