<?php

namespace App\Http\Controllers;

use App\Actions\CreateUser;
use App\Actions\UpdateUser;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Traits\RoleManagement;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inertia::render('User/Index', [
          'users' => User::with(['roles'])
            ->filter($request->only(['text']))
            ->orderBy('name')
            ->paginate()
            ->withQueryString()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('User/Create', [
          //'roles' => Role::orderBy('name')->get(),
          'roles' => Role::all()->map(fn($role) => [
            'id' => $role->id,
            'name' => $role->name
          ]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $storeUserRequest, CreateUser $createUser)
    {
        $createUser->handle($storeUserRequest);
        return redirect()->route('user.index')
          ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $user->loadMissing('roles');
        return Inertia::render('User/Edit', [
          'user' => new UserResource($user),
          'roles' => Role::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $updateUserRequest, UpdateUser $updateUser, User $user)
    {
        $updateUser->handle($updateUserRequest, $user);
        return redirect()->route('user.index')
          ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()
          ->back()
          ->with('success', 'User deleted successfully.');
    }
}
