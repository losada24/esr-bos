<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtraWork extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'unit',
        'planned',
        'notes',
       
    ];

    public function orderProductExtraWorks(): HasMany {
        return $this->hasMany(OrderProductExtraWork::class, 'order_id', 'id');
      }

}
