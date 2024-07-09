<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'name',
        'notes',
        'type_of_products_id',
    ];

    public function typeOfProduct(): BelongsTo {
        return $this->belongsTo(TypeOfProduct::class, 'type_of_products_id', 'id');
    }

    public function productConfigs(): HasMany {
        return $this->hasMany(ProductConfig::class, 'product_categories_id', 'id');
    }
}
