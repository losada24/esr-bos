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
        'company_id',
        'markup',
        'promotion',
        'created_by'
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

    public function scopeCreatedByCheck(Builder $query): void
    {
      if (!auth()->user()->hasRole(RoleEnum::$ADMIN)) {
        $query->where('created_by', auth()->user()->id);
      }
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['text'] ?? null, function ($query, $search) {
          $query->where(DB::raw("CONCAT(name, ' ', email)"), 'like', '%'.$search.'%');
        });
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function isCreatedByLoggedUser() {
        return $this->created_by == auth()->user()->id;
    }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getRedirectRoute() {
        if ($this->hasRole(RoleEnum::$ADMIN) ||
          $this->hasRole(RoleEnum::$PRODUCTION) ||
          $this->hasRole(RoleEnum::$ACCOUNT_MANAGER) ||
          $this->hasRole(RoleEnum::$SHIPPING) ||
          $this->hasRole(RoleEnum::$ACCOUNTING)) {
            return 'order';
        }
            
        return 'estimate';
    }
  }
