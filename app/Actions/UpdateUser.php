<?php
namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;

class UpdateUser {

  public function handle(Request $request, User $user) {

    DB::transaction(function() use ($request, $user) {

      if( !$user )
      {
          throw new \Exception('Not not updated');
      }

      $company_id = auth()->user()->company_id;
      if (auth()->user()->hasRole(RoleEnum::$ADMIN)) {
        $company_id = $request->company_id;
      }

      $userData = [
        'name' => $request->name,
        'email' => $request->email,
        'company_id' => $company_id,
      ];

      if ($request->password) {
        $userData['password'] = Hash::make($request->password);
      }

      $user->update($userData);
      $user->syncRoles([$request->role]);
    });
  }
}
