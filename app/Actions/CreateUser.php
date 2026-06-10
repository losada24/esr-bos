<?php
namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use App\Jobs\SendGmailEmail;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

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
        'status' => $request->status,
      ]);

      if( !$user )
      {
          throw new \Exception('User not created');
      }

      $roleIds = collect($request->input('role', []))
        ->map(fn ($roleId) => (int) $roleId)
        ->filter(fn (int $roleId) => $roleId > 0)
        ->values()
        ->all();

      if ($roleIds !== []) {
        $user->syncRoles($roleIds);
      }

      $selectedRoleNames = Role::query()
        ->whereIn('id', $roleIds)
        ->pluck('name')
        ->all();

      $delegatedOwnerIds = [];
      if (in_array(RoleEnum::OWNER->value, $selectedRoleNames, true)) {
        $delegatedOwnerIds = User::role(RoleEnum::OWNER->value)
          ->whereIn('id', collect($request->input('delegated_owner_ids', []))
            ->map(fn ($ownerId) => (int) $ownerId)
            ->filter(fn (int $ownerId) => $ownerId > 0 && $ownerId !== (int) $user->id)
            ->values()
            ->all())
          ->pluck('id')
          ->all();
      }

      $user->delegatedOwners()->sync($delegatedOwnerIds);
      
       //$user->assignRole($request->role);

       // Mail::to($request->email, $request->name)->send(new \App\Mail\NewUserRegistration($request->name, $request->email, $request->password));
        $newUserRegistration = new \App\Mail\NewUserRegistration($request->name, $request->email, $request->password);
        SendGmailEmail::dispatch($request->email, $newUserRegistration)->onQueue('emails');
    });
  }
}
