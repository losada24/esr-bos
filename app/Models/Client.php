<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'name',
      'last_name',
      'phone',
      'email',
      'address',
      'city',
      'state',
      'zip',
    ];

    public function orders(): HasMany {
      return $this->hasMany(Order::class);
    }

}
