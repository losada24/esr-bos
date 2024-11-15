<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    protected function casts(): array
    {
        return [
            'vip_clients' => 'boolean'
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
          $query->where(DB::raw("CONCAT(name, ' ', email, ' ', phone)"), 'like', '%'.$search.'%');
        });
    }

    public function user(): BelongsTo {
      return $this->belongsTo(User::class);
    }
    
    public function orders(): HasMany {
      return $this->hasMany(Order::class);
    }

    public function clientAddress(): HasMany {
      return $this->hasMany(ClientAddress::class);
    }

}
