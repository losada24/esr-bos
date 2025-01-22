<?php
namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class UpdateUser {

  public function handle(Request $request, User $user) {

    DB::transaction(function() use ($request, $user) {

      if( !$user )
      {
          throw new \Exception('Not not updated');
      }

      $reaturedImagePath = $user->featured_image;
      if ($request->hasFile('featured_image')) {
        $fileName = time() . '_' . $request->file('featured_image')->getClientOriginalName();
        $tempOldImagePath = $reaturedImagePath;
        $reaturedImagePath = $request->file('featured_image')->storeAs('user_images', $fileName, 'public');
        if ($reaturedImagePath && $tempOldImagePath) {
          Storage::disk('public')->delete($tempOldImagePath);
        }
      }

      $userData = [
        'name' => $request->name,
        'email' => $request->email,
        'featured_image' => $reaturedImagePath,
        'phone' => $request->phone,
      ];

      if ($request->password) {
        $userData['password'] = Hash::make($request->password);
        Mail::to($request->email, $request->name)->send(new \App\Mail\UpdateUserInformation($request->name, $request->email, $request->password));
      }

      $user->update($userData);
      $user->syncRoles([$request->role]);
    });
  }
}
