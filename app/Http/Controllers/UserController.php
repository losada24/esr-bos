<?php

namespace App\Http\Controllers;

use App\Actions\CreateUser;
use App\Enum\RoleEnum;
use App\Actions\UpdateUser;
use App\Enum\StatusUserEnum;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Referral;
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
          'owner_options' => User::assignableOrderOwner()
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->orderBy('name')
            ->get(),
          'statuses' => collect(StatusUserEnum::cases())->map(fn (StatusUserEnum $status) => [
            'value' => $status->value,
            'label' => ucwords(strtolower(str_replace('_', ' ', $status->value))),
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
        $user->loadMissing('roles', 'delegatedOwners');
        return Inertia::render('User/Edit', [
          'user' => new UserResource($user),
          'roles' => Role::orderBy('name')->get(),
          'owner_options' => User::assignableOrderOwner()
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(),
          'statuses' => collect(StatusUserEnum::cases())->map(fn (StatusUserEnum $status) => [
            'value' => $status->value,
            'label' => ucwords(strtolower(str_replace('_', ' ', $status->value))),
          ]),
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

    public function referredClients(Request $request)
    {
        $authenticatedUser = $request->user();

        $canViewAllReferrals = $authenticatedUser->hasAnyRole([
            RoleEnum::ADMIN->value,
            RoleEnum::FRONTDESK_ADMIN->value,
        ]);

        $referrals = Referral::query()
            ->select([
                'id',
                'name',
                'phone',
                'email',
                'type',
                'client_id',
                'user_id',
            ])
            ->with([
                'clients' => function ($query) {
                    $query
                        ->select([
                            'clients.id',
                            'clients.name',
                            'clients.email',
                            'clients.phone',
                            'clients.source',
                            'clients.created_at',
                            'clients.referral_id',
                            'clients.company_contact_id',
                        ])
                        ->with('companyContact:id,name')
                        ->orderBy('clients.name');
                },
                'referrerUser:id,name,email,phone',
                'referrerClient:id,name,email,phone',
            ])
            ->withCount('clients')
            ->has('clients')
            ->when(! $canViewAllReferrals, function ($query) use ($authenticatedUser) {
                $query->where('user_id', $authenticatedUser->id);
            })
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('User/ReferredClients', [
            'referrals' => $referrals,
            'can_view_all_referrals' => $canViewAllReferrals,
        ]);
    }

    public function searchReferrers(Request $request)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $term = trim($data['q']);
        if ($term === '') {
            return response()->json(['data' => []]);
        }

        $digits = preg_replace('/\D+/', '', $term) ?? '';
        $like = '%' . $term . '%';

        $users = User::query()
            ->select('id', 'name', 'phone', 'email', 'status')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', RoleEnum::CUSTOMER->value);
            })
            ->where(function ($query) use ($like, $digits) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);

                if ($digits !== '') {
                    $query->orWhere('phone', 'like', '%' . $digits . '%');
                } else {
                    $query->orWhere('phone', 'like', $like);
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json(['data' => $users]);
    }
}
