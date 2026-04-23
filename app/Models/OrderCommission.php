<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderCommission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'beneficiary_source_type',
        'beneficiary_source_id',
        'beneficiary_relation',
        'beneficiary_name_snapshot',
        'beneficiary_email_snapshot',
        'status',
        'calculation_type',
        'percentage_value',
        'fixed_amount',
        'project_amount_snapshot',
        'fee_amount_snapshot',
        'financing_fee_amount',
        'base_amount_snapshot',
        'commission_amount',
        'other_cost_amount',
        'other_cost_notes',
        'total_amount',
        'paid_amount',
        'pending_amount',
        'next_payment_id',
        'created_by',
        'updated_by',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderCommissionPayment::class)->orderBy('sequence');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(OrderCommissionAudit::class, 'order_commission_id');
    }

    public function nextPayment(): BelongsTo
    {
        return $this->belongsTo(OrderCommissionPayment::class, 'next_payment_id');
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
