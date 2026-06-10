<?php
namespace App\Actions;

use App\Models\Attachment;
use App\Models\Biweekly;
use App\Models\Company;
use App\Models\InstallationTeam;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreateSource {

  public function handle(Request $request) {
    DB::transaction(function() use ($request) {

      $source = Source::create([
        'name' => $request->name,
        'description' => $request->description,
      ]);

      if( !$source )
      {
          throw new \Exception('Source not created');
      }

    });
  }
}
