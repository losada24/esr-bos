<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderCommissionPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_commission_id',
        'sequence',
        'status',
        'split_type',
        'split_value',
        'payment_base_amount',
        'other_cost_amount',
        'other_cost_notes',
        'total_to_pay',
        'commission_period_id',
        'paid_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $dates = [
        'paid_at',
    ];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(OrderCommission::class, 'order_commission_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(CommissionPeriod::class, 'commission_period_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
