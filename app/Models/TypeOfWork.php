<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeOfWork extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'name',
      'notes',
    ];

    
    public function productCosts(): HasMany {
      return $this->hasMany(ProductCost::class, 'type_of_work_id', 'id');
    }

    public function orders(): HasMany {
      return $this->hasMany(Order::class);
    }

    public function orderProducts(): HasMany {
      return $this->hasMany(OrderProduct::class);
    }
}

