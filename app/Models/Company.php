<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Enum\RoleEnum;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
      'allow_credit_payment' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
      'name',
      'phone_number',
      'email',
      'address',
      'city',
      'state',
      'zip',
      'featured_image',
      'user_id',
      'markup',
      'promotion',
      'allow_credit_payment'
    ];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['text'] ?? null, function ($query, $search) {
          $query->where(DB::raw("CONCAT(name, ' ', phone_number, ' ', address, ' ', zip, ' ', city)"), 'like', '%'.$search.'%');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
