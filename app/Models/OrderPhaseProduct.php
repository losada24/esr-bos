<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPhaseProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_phase_id',
        'order_product_id',
        'qty',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(OrderPhase::class, 'order_phase_id');
    }

    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class);
    }
}
