<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Enum\RoleEnum;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
      'name',
      'email',
      'phone',
      'address',
      'city',
      'state',
      'zip',
      'user_id',
      'company_id'
    ];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['text'] ?? null, function ($query, $search) {
          $query->where(DB::raw("CONCAT(name, ' ', email, ' ', phone, ' ', zip, ' ', city)"), 'like', '%'.$search.'%');
        });
    }

    protected static function booted(): void
    {
      static::addGlobalScope('role', function (Builder $query) {
        if (auth()->user()->hasRole(RoleEnum::$CLIENT_ADMIN )) {
          $query->where('company_id', auth()->user()->company_id);
        }
      });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
