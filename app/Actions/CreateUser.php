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
        'reference_code' => $this->getUniqueReferenceCode(),
      ]);

      if( !$user )
      {
          throw new \Exception('User not created');
      }
      
      $user->assignRole($request->role);
    });
  }

  public function getUniqueReferenceCode() {
    $reference_code = uniqid();
    $user = User::where('reference_code', $reference_code)->first();
    if( $user ) {
      $this->getUniqueReferenceCode();
    }
    return $reference_code;
  }
}
