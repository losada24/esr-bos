<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Referral extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'type',
        'client_id',
        'user_id',
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
            $query->where(DB::raw("CONCAT_WS(' ', name, email, phone)"), 'like', '%'.$search.'%');
        });
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function referrerClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function referrerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
