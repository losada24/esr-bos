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
