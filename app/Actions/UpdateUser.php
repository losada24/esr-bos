<?php
namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use App\Jobs\SendGmailEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

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
        'status' => $request->status,
      ];

      if ($request->password) {
        $userData['password'] = Hash::make($request->password);
        // Mail::to($request->email, $request->name)->send(new \App\Mail\UpdateUserInformation($request->name, $request->email, $request->password));
        $updateUserInformation = new \App\Mail\UpdateUserInformation($request->name, $request->email, $request->password);
        SendGmailEmail::dispatch($request->email, $updateUserInformation)->onQueue('emails');
      }

      $user->update($userData);
      $roleIds = collect($request->input('role', []))
        ->map(fn ($roleId) => (int) $roleId)
        ->filter(fn (int $roleId) => $roleId > 0)
        ->values()
        ->all();

      $user->syncRoles($roleIds);

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
    });
  }
}
