<?php

namespace App\Models;

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

    public function user(): BelongsTo {
      return $this->belongsTo(User::class);
    }
    
    public function clients(): HasMany {
      return $this->hasMany(Client::class);
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
