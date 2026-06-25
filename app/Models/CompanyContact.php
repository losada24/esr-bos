<?php

namespace App\Models;

use App\Enum\RoleEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class CompanyContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'name',
      'phone',
      'email',
      'website',
      'billing_street',
      'billing_city',
      'billing_state',
      'billing_code',
      'bid_due_date',

    ];


    protected $dates = [
      'bid_due_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    public function getUpdatedAtAttribute($value)
    {
        return date('m/d/Y', strtotime($value));
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['text'] ?? null, function ($query, $search) {
          $query->where(function ($subQuery) use ($search) {
            $subQuery->where('name', 'like', '%'.$search.'%')
              ->orWhere('email', 'like', '%'.$search.'%')
              ->orWhere('phone', 'like', '%'.$search.'%');
          });
        });
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
      if (!$user) {
        return $query->whereRaw('1 = 0');
      }

      $isRestrictedOwner = $user->hasRole(RoleEnum::OWNER->value)
        && !$user->hasAnyRole([
          RoleEnum::ADMIN->value,
          RoleEnum::ACCOUNT_MANAGER->value,
          RoleEnum::OWNER_ADMIN->value,
          RoleEnum::FRONTDESK_ADMIN->value,
      ]);

      if ($isRestrictedOwner) {
        $query->where(function (Builder $companyQuery) use ($user) {
          $companyQuery
            ->whereHas('clients', function (Builder $clientQuery) use ($user) {
              $clientQuery->where('clients.user_id', $user->id);
            })
            ->orWhereIn('company_contacts.id', Client::query()
              ->select('company_contact_id')
              ->where('user_id', $user->id)
              ->whereNotNull('company_contact_id'));
        });
      }

      return $query;
    }

    public function user(): BelongsTo {
      return $this->belongsTo(User::class);
    }
    
    public function clients(): BelongsToMany {
      return $this->belongsToMany(Client::class, 'client_company_contacts')
        ->wherePivotNull('deleted_at')
        ->withPivot(['is_primary', 'deleted_at', 'deleted_by_user_id'])
        ->withTimestamps();
    }

    public function clientCompanyContacts(): HasMany
    {
      return $this->hasMany(ClientCompanyContact::class);
    }

    public function clientAddress(): HasMany {
      return $this->hasMany(ClientAddress::class);
    }

    public function orderCompanyContacts(): HasMany
    {
      return $this->hasMany(OrderCompanyContact::class);
    }

    public function orders(): BelongsToMany
    {
      return $this->belongsToMany(Order::class, 'order_company_contacts')
        ->withPivot(['client_id', 'source_id', 'is_selected', 'selected_at'])
        ->withTimestamps();
    }

}
