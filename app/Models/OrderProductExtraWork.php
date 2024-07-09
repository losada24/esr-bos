<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProductExtraWork extends Model
{
   
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        
        'order_product_id',
        'extra_work_id',
        'number_of_sides',
        'price',
        'notes'
    ];

    public function orderProduct(): BelongsTo {
        return $this->belongsTo(OrderProduct::class, 'order_product_id', 'id');
    }

    public function extraWork(): BelongsTo {
        return $this->belongsTo(ExtraWork::class, 'extra_work_id', 'id');
    }

}
