<?php
namespace App\Actions;

use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateRawMaterial {

  public function handle(Request $request, RawMaterial $rawMaterial) {

    DB::transaction(function() use ($request, $rawMaterial) {

      if( !$rawMaterial )
      {
          throw new \Exception('Raw Material not updated');
      }

      $reaturedImagePath = $rawMaterial->featured_image;
      if ($request->hasFile('featured_image')) {
        $fileName = time() . '_' . $request->file('featured_image')->getClientOriginalName();
        $tempOldImagePath = $reaturedImagePath;
        $reaturedImagePath = $request->file('featured_image')->storeAs('raw_materials_images', $fileName, 'public');
        if ($reaturedImagePath && $tempOldImagePath) {
          Storage::disk('public')->delete($tempOldImagePath);
        }
      }
      
      $rawMaterialData = [
        'name' => $request->name,
        'qty' => $request->qty,
        'unit_of_measurement' => $request->unit_of_measurement,
        'cost_per_unit' => $request->cost_per_unit,
        'notes' => $request->notes,
        'featured_image' => $reaturedImagePath,
        'user_id' => auth()->user()->id,
      ];

      $rawMaterial->update($rawMaterialData);
    });
  }
}
