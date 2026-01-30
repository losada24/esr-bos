<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'name',
      'phone',
      'email',
      'user_id',
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

    public function user(): BelongsTo {
      return $this->belongsTo(User::class);
    }

    public function companyContact(): BelongsTo {
      return $this->belongsTo(CompanyContact::class);
    }
    
    public function orders(): HasMany {
      return $this->hasMany(Order::class);
    }

    public function clientAddress(): HasMany {
      return $this->hasMany(ClientAddress::class);
    }

    public function referral()
    {
        return $this->belongsTo(Referral::class);
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

}
