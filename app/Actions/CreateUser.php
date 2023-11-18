<?php
namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;

class CreateUser {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {

      $company_id = auth()->user()->company_id;

      if (auth()->user()->hasRole(RoleEnum::$ADMIN)) {
        $company_id = $request->company_id;
      }

      $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'company_id' => $company_id,
        'created_by' => auth()->user()->id
      ]);

      if( !$user )
      {
          throw new \Exception('User not created');
      }
      
       $user->assignRole($request->role);
    });
  }
}
