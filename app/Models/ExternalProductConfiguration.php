<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalProductConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'external_products_configurations';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
      'external_product',
      'width',
      'height',
      'price',
      'extras',
      'notes',
    ];

    /*public function externalProduct()
    {
        return $this->belongsTo(ExternalProduct::class);
    }*/
}
