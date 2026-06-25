<?php

namespace App\Models;

use App\Enum\RoleEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [
        'company_contact_ids',
    ];

    protected $fillable = [
      'name',
      'phone',
      'email',
      'user_id',
      'created_by_user_id',
      'mobile_user_id',
      'vip_clients',
      'vip_notes',
      'contact_type',
      'other_phone',
      'secondary_email',
      'source',
      'company_contact_id',
      'is_contact',
      'referral_id', // Foreign key to Referral model

    ];

    protected function casts(): array
    {
        return [
            'vip_clients' => 'boolean',
            'is_contact' => 'boolean',
        ];
    }

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
          $query->where(DB::raw("CONCAT_WS(' ', name, email, phone)"), 'like', '%'.$search.'%');
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
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public function user(): BelongsTo {
      return $this->belongsTo(User::class);
    }

    public function createdByUser(): BelongsTo {
      return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function mobileUser(): BelongsTo {
      return $this->belongsTo(User::class, 'mobile_user_id');
    }

    public function companyContact(): BelongsTo {
      return $this->belongsTo(CompanyContact::class);
    }

    public function companyContacts(): BelongsToMany
    {
      return $this->belongsToMany(CompanyContact::class, 'client_company_contacts')
        ->wherePivotNull('deleted_at')
        ->withPivot(['is_primary', 'deleted_at', 'deleted_by_user_id'])
        ->withTimestamps();
    }

    public function clientCompanyContacts(): HasMany
    {
        return $this->hasMany(ClientCompanyContact::class);
    }
    
    public function orders(): HasMany {
      return $this->hasMany(Order::class);
    }

    public function clientAddress(): HasMany {
      return $this->hasMany(ClientAddress::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function referralProfile(): HasOne
    {
        return $this->hasOne(Referral::class, 'client_id');
    }

    public function referredClients(): HasManyThrough
    {
        return $this->hasManyThrough(
            Client::class,
            Referral::class,
            'client_id',
            'referral_id',
            'id',
            'id'
        );
    }

    public function orderClientTemps()
    {
        return $this->hasMany(OrderClientTemps::class);
    }

    public function orderCompanyContacts(): HasMany
    {
        return $this->hasMany(OrderCompanyContact::class);
    }

    public function commercialOrders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_company_contacts')
            ->withPivot(['company_contact_id', 'source_id', 'is_selected', 'selected_at'])
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->morphMany(Tag::class, 'taggable');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function getCompanyContactIdsAttribute(): array
    {
        if ($this->relationLoaded('companyContacts')) {
            return $this->companyContacts
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        if (!empty($this->company_contact_id)) {
            return [(int) $this->company_contact_id];
        }

        return [];
    }

}
