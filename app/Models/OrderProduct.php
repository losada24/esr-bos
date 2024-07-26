<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderProduct extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'order_id',
        'product_config_id',
        'type_of_work_id',
        'height',
        'width',
        'qty',
        'unit_price',
        'total_price',
        'unit_price_with_extraworks',
        'total_price_with_extraworks',
        'notes',
        'storefront_area',
        'installation_other_level',
        'product_category_id',
        'type_of_product_id',
    ];

    public function order(): BelongsTo {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function productConfig(): BelongsTo {
        return $this->belongsTo(ProductConfig::class, 'product_config_id', 'id');
    }
    
    public function productCategory(): BelongsTo {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }

    public function typeOfProduct(): BelongsTo {
        return $this->belongsTo(TypeOfProduct::class, 'type_of_product_id', 'id');
    }

    public function typeOfWork(): BelongsTo {
        return $this->belongsTo(TypeOfWork::class, 'type_of_work_id', 'id');
    }

    public function orderProductExtraWorks(): BelongsToMany {
        return $this->belongsToMany(
          ExtraWork::class, 
          'order_products_extra_works', 
          'order_product_id', 'extra_work_id'
          )->using(OrderProductExtraWork::class)
            ->withPivot('price', 'number_of_sides')
            ->withTimestamps();
      }
}
