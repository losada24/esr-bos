<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'name',
      'price',
      'notes',
    ];

    public function orders(): HasMany {
      return $this->hasMany(Order::class);
    }

    public function configDateEstimates(): HasMany {
      return $this->hasMany(ConfigDateEstimation::class);
    }
}
