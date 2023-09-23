<?php
namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UpdateUser {

  public function handle(Request $request, User $user) {

    DB::transaction(function() use ($request, $user) {

      if( !$user )
      {
          throw new \Exception('Order not updated');
      }
      
      $userData = [
        'name' => $request->name,
        'email' => $request->email,
      ];

      if ($request->password) {
        $userData['password'] = Hash::make($request->password);
      }

      $user->update($userData);
      $user->syncRoles([$request->role]);
    });
  }
}
