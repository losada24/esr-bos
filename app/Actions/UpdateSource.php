<?php
namespace App\Actions;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use App\Models\Biweekly;
use App\Models\Source;

class UpdateSource {

  public function handle(Request $request, Source $source) {

    DB::transaction(function() use ($request, $source) {

      if( !$source )
      {
          throw new \Exception('Not not updated');
      }

      $sourceData = [
        'name' => $request->name,
        'description' => $request->description,
      ];

      $source->update($sourceData);
    });
  }
}
