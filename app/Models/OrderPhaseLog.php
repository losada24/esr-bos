<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPhaseLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_phase_id',
        'order_id',
        'user_id',
        'action',
        'status',
        'before',
        'after',
        'notes',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(OrderPhase::class, 'order_phase_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
