<?php
namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CreateUser {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {
      $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
      ]);

      if( !$user )
      {
          throw new \Exception('User not created');
      }

      if( $request->is_admin )
      {
          $user->assignRole('admin');
      } else {
          $user->givePermissionTo($request->permissions);
      }
    });
  }
}
