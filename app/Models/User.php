<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enum\RoleEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
       // 'company_id',
        'markup',
        'promotion',
        'created_by',
        'featured_image',
        'phone',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /* public function scopeCreatedByCheck(Builder $query): void
    {
      $isAccountManager = auth()->user()->hasRole(RoleEnum::$ACCOUNT_MANAGER);
      if (!auth()->user()->hasRole(RoleEnum::$ADMIN) && !$isAccountManager) {
        $query->where('created_by', auth()->user()->id);
      }

      if ($isAccountManager) {
        $query->whereHas('roles', function ($query) {
          $query->where('name', '<>', RoleEnum::$ADMIN);
        });
      }
    } */

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['text'] ?? null, function ($query, $search) {
          $query->where(DB::raw("CONCAT(name, ' ', email)"), 'like', '%'.$search.'%');
        });
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function mobileClients(): HasMany
    {
        return $this->hasMany(Client::class, 'mobile_user_id');
    }

    public function referralProfile(): HasOne
    {
        return $this->hasOne(Referral::class, 'user_id');
    }

    public function referredClients(): HasManyThrough
    {
        return $this->hasManyThrough(
            Client::class,
            Referral::class,
            'user_id',
            'referral_id',
            'id',
            'id'
        );
    }

    public function isCreatedByLoggedUser() {
        return $this->created_by == auth()->user()->id;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function delegatedOwners(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'owner_delegate_access',
            'viewer_owner_id',
            'target_owner_id'
        )->withTimestamps();
    }

    public function delegatedOwnerViewers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'owner_delegate_access',
            'target_owner_id',
            'viewer_owner_id'
        )->withTimestamps();
    }

    public function accessibleOwnerIds(): array
    {
        $delegatedOwnerIds = $this->relationLoaded('delegatedOwners')
            ? $this->delegatedOwners->pluck('id')
            : $this->delegatedOwners()->pluck('users.id');

        return $delegatedOwnerIds
            ->push($this->id)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
  }
