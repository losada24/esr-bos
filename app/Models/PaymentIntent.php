<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentIntent extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'payment_type',
        'payment_id',
        'order_id',
        'amount',
        'channel',
        'provider',
        'provider_payment_link_id',
        'provider_payment_request_id',
        'provider_reference',
        'provider_payment_url',
        'provider_status',
        'provider_metadata',
        'status',
        'expires_at',
        'used_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_metadata' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
