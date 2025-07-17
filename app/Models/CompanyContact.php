<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
          $query->where(DB::raw("CONCAT(name, ' ', email, ' ', phone)"), 'like', '%'.$search.'%');
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

}
