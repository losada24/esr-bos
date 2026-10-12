<?php

namespace App\Models;

use App\Enum\OrderStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStageOverdueExtension extends Model
{
    public const MAX_CUMULATIVE_BUSINESS_DAYS = 30;

    public static function hasUnlimitedBusinessDayExtensions(?string $status): bool
    {
        return $status === OrderStatusEnum::FOLLOW_UP_PROJECTS->value;
    }

    protected $fillable = [
        'order_id',
        'order_stage_overdue_id',
        'user_id',
        'status',
        'stage_started_at',
        'business_days',
        'extended_until',
        'note',
    ];

    protected $casts = [
        'stage_started_at' => 'datetime',
        'extended_until' => 'datetime',
        'business_days' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function overdue(): BelongsTo
    {
        return $this->belongsTo(OrderStageOverdue::class, 'order_stage_overdue_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
