<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function orderProductExtraWorks(): BelongsToMany {
        return $this->belongsToMany(
          OrderProduct::class, 'order_products_extra_works', 'extra_work_id', 'order_product_id'
          )->using(OrderProductExtraWork::class)
            ->withPivot('price', 'amount')
            ->withTimestamps();
    }

    public function typeOfProducts(): BelongsToMany {
        return $this->belongsToMany(
          TypeOfProduct::class, 'extra_work_type_of_products', 'extra_work_id', 'type_of_product_id'
          )->withTimestamps();
    }

}
