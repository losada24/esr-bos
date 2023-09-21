<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Referred extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'referrals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
      'user_id',
      'name',
      'email',
      'phone',
      'notes',
      'status'
    ];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['status'] ?? null, function ($query, $search) {
          $query->where('status', $search);
        })->when($filters['text'] ?? null, function ($query, $search) {
          $query->where(DB::raw("CONCAT(name, ' ', email, ' ',phone)"), 'like', '%'.$search.'%');
        })->when($filters['user_id'] ?? null, function ($query, $search) {
          $query->where('user_id', $search);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referralsStatusUpdate() 
    {
        return $this->hasMany(ReferralsStatusUpdates::class, 'referral_id');
    }
}
