<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductConfig extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'name',
        'notes',
        'product_categories_id',
    ];

    public function productCategory(): BelongsTo {
        return $this->belongsTo(ProductCategory::class, 'product_categories_id', 'id');
    }
    
    public function productCosts(): HasMany {
        return $this->hasMany(ProductCost::class, 'product_config_id', 'id');
    }

    public function orderProducts(): HasMany {
        return $this->hasMany(OrderProduct::class, 'product_config_id', 'id');
    }
}

