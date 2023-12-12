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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
      'name',
      'phone_number',
      'address',
      'city',
      'state',
      'zip',
      'featured_image',
      'user_id',
      'markup',
      'promotion'
    ];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['text'] ?? null, function ($query, $search) {
          $query->where(DB::raw("CONCAT(name, ' ', phone_number, ' ', address, ' ', zip, ' ', city)"), 'like', '%'.$search.'%');
        });
    }

    /*protected static function booted(): void
    {
      static::addGlobalScope('role', function (Builder $query) {
        if (auth()->user()->hasRole(RoleEnum::$CLIENT_ADMIN )) {
          $query->where('user_id', auth()->user()->id);
        }
      });
    } */

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
