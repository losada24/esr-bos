<?php
namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use App\Jobs\SendGmailEmail;
use Illuminate\Support\Facades\Mail;

class CreateUser {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {

      $reaturedImagePath = null;
      if ($request->hasFile('featured_image')) {
        $fileName = time() . '_' . $request->file('featured_image')->getClientOriginalName();
        $reaturedImagePath = $request->file('featured_image')->storeAs('user_images', $fileName, 'public');
      }

      $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'featured_image' => $reaturedImagePath,
        'phone' => $request->phone,
      ]);

      if( !$user )
      {
          throw new \Exception('User not created');
      }
          if ($request->role && is_array($request->role)) {
            $user->syncRoles($request->role);
        }
      
       //$user->assignRole($request->role);

       // Mail::to($request->email, $request->name)->send(new \App\Mail\NewUserRegistration($request->name, $request->email, $request->password));
        $newUserRegistration = new \App\Mail\NewUserRegistration($request->name, $request->email, $request->password);
        SendGmailEmail::dispatch($request->email, $newUserRegistration)->onQueue('emails');
    });
  }
}
