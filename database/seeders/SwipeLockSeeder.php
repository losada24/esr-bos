<?php

namespace Database\Seeders;

use App\Enum\UnitOfMeasurement;
use App\Models\RawMaterial;

use Illuminate\Database\Seeder;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;

class SwipeLockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $raw_material_slock_w = RawMaterial::create([
          'name' => 'SLOCK 0001 W',
          'unit_of_measurement' => UnitOfMeasurement::$UNIT_OF_MEASUREMENT['UNIT'],
          'qty' => 20000,
          'cost_per_unit' => 1.10,
          'notes' => 'Swipe Lock White',
          'featured_image' => 'raw_materials_images/1702007987_NO-IMAGE.jpg',
          'user_id' => 1
        ]);

        $raw_material_slock_bl = RawMaterial::create([
          'name' => 'SLOCK 0001 BL',
          'unit_of_measurement' => UnitOfMeasurement::$UNIT_OF_MEASUREMENT['UNIT'],
          'qty' => 20000,
          'cost_per_unit' => 1.10,
          'notes' => 'Swipe Lock Black',
          'featured_image' => 'raw_materials_images/1702007987_NO-IMAGE.jpg',
          'user_id' => 1
        ]);
    }
}
