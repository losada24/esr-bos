<?php

namespace App\Models;

use App\Support\PaymentInstallmentAccounting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_schedule_id',
        'position',
        'label',
        'percentage',
        'amount',
        'due_date',
        'status',
        'paid_at',
        'paid_by',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PaymentSchedule::class, 'payment_schedule_id');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(PaymentInstallmentMovement::class, 'payment_installment_id')
            ->orderByDesc('paid_at')
            ->orderByDesc('id');
    }

    public function syncPaymentState(): void
    {
        $paidAmount = (float) $this->movements()->sum('amount');
        $summary = PaymentInstallmentAccounting::summarize((float) $this->amount, $paidAmount);

        $latestMovement = $this->movements()->first();

        $this->status = $summary['status'];
        $this->paid_at = $latestMovement?->paid_at;
        $this->paid_by = $latestMovement?->paid_by;
        $this->save();
    }
}
