<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPaymentInformationAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'source',
        'changed_at',
        'ip_address',
        'user_agent',
        'changes',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'changes' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
