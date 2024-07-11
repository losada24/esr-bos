<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCost extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'notes',
        'price',
        'difficult_hight_price',
        'type_of_work_id',
        'product_config_id'
    ];
    
    public function typeOfWork(): BelongsTo {
        return $this->belongsTo(TypeOfWork::class, 'type_of_work_id', 'id');
    }

    public function productConfig(): BelongsTo {
        return $this->belongsTo(ProductConfig::class, 'product_config_id', 'id');
    }

    

}
