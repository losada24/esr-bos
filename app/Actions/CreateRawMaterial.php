<?php
namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RawMaterial;

class CreateRawMaterial {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {

      $reaturedImagePath = null;
      if ($request->hasFile('featured_image')) {
        $fileName = time() . '_' . $request->file('featured_image')->getClientOriginalName();
        $reaturedImagePath = $request->file('featured_image')->storeAs('raw_materials_images', $fileName, 'public');
      }

      $rawMaterial = RawMaterial::create([
        'name' => $request->name,
        'qty' => $request->qty,
        'unit_of_measurement' => $request->unit_of_measurement,
        'cost_per_unit' => $request->cost_per_unit,
        'notes' => $request->notes,
        'featured_image' => $reaturedImagePath,
        'user_id' => auth()->user()->id,
      ]);
      
      if( !$rawMaterial )
      {
          throw new \Exception('Raw Material not created');
      }
    });
  }
}
