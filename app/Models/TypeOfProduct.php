<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeOfProduct extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'notes',
    ];

    public function productCategories(): HasMany {
        return $this->hasMany(ProductCategory::class, 'type_of_products_id', 'id');
    }

    public function extraWorks(): BelongsToMany {
      return $this->belongsToMany(
        ExtraWork::class, 'extra_work_type_of_products', 'type_of_product_id', 'extra_work_id'
        )->withTimestamps();
  }

}
