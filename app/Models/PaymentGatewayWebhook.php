<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'notification_id',
        'webhook_id',
        'event_type',
        'event_date',
        'signature_header',
        'signature_valid',
        'source_ip',
        'headers_json',
        'raw_body',
        'payload_json',
        'payload_entity_name',
        'payload_entity_id',
        'gateway_transaction_id',
        'merchant_reference_id',
        'channel',
        'amount',
        'response_code',
        'order_id',
        'payment_installment_id',
        'order_payment_id',
        'matched_by',
        'processing_status',
        'processing_error',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'headers_json' => 'array',
        'payload_json' => 'array',
        'signature_valid' => 'boolean',
        'amount' => 'decimal:2',
        'event_date' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentInstallment(): BelongsTo
    {
        return $this->belongsTo(PaymentInstallment::class);
    }

    public function orderPayment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class);
    }
}
