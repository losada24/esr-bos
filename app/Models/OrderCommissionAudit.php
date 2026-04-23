<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCommissionAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_commission_id',
        'order_commission_payment_id',
        'commission_period_id',
        'user_id',
        'source',
        'action',
        'changed_at',
        'changes',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'changes' => 'array',
    ];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(OrderCommission::class, 'order_commission_id')->withTrashed();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(OrderCommissionPayment::class, 'order_commission_payment_id')->withTrashed();
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(CommissionPeriod::class, 'commission_period_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
