<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderStageOverdue extends Model
{
    protected $fillable = [
        'order_id',
        'order_status_id',
        'status',
        'stage_started_at',
        'limit_business_days',
        'business_days_elapsed',
        'detected_at',
        'resolved_at',
        'resolved_business_days_elapsed',
        'is_active',
    ];

    protected $casts = [
        'stage_started_at' => 'datetime',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(OrderStageOverdueExtension::class);
    }

    public function latestExtension(): HasOne
    {
        return $this->hasOne(OrderStageOverdueExtension::class)->latestOfMany();
    }
}
