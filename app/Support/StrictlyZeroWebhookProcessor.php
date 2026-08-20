<?php

namespace App\Support;

use App\Models\OrderPayment;
use App\Models\PaymentGatewayWebhook;
use App\Models\PaymentInstallment;
use App\Models\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StrictlyZeroWebhookProcessor
{
    private const PROVIDER = 'STRICTLY_ZERO';
    private const STATUS_RECEIVED = 'RECEIVED';
    private const STATUS_INVALID_AUTH = 'INVALID_AUTH';
    private const STATUS_UNMATCHED = 'UNMATCHED';
    private const STATUS_INCOMPLETE = 'INCOMPLETE';
    private const STATUS_MATCHED = 'MATCHED';
    private const STATUS_PROCESSED = 'PROCESSED';
    private const STATUS_IGNORED = 'IGNORED';
    private const STATUS_FAILED = 'FAILED';

    public function __construct(
        private readonly StrictlyZeroPaymentLinkService $paymentLinkService
    ) {
    }

    public function handle(Request $request): array
    {
        $rawBody = (string) $request->getContent();
        $payload = json_decode($rawBody, true);
        $payload = is_array($payload) ? $payload : [];

        $notificationId = $this->notificationId($payload, $rawBody);
        $existing = PaymentGatewayWebhook::query()
            ->where('notification_id', $notificationId)
            ->first();

        if ($existing) {
            return ['duplicate' => true, 'webhook' => $existing, 'authorized' => true];
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $paymentLinkId = isset($data['paymentLinkId']) ? (string) $data['paymentLinkId'] : null;

        $webhook = PaymentGatewayWebhook::create([
            'provider' => self::PROVIDER,
            'notification_id' => $notificationId,
            'webhook_id' => isset($payload['_id']) ? (string) $payload['_id'] : null,
            'event_type' => (string) ($payload['action'] ?? 'unknown'),
            'signature_header' => $request->header('Authorization'),
            'signature_valid' => false,
            'source_ip' => $request->ip(),
            'headers_json' => $this->headersForStorage($request),
            'raw_body' => $rawBody,
            'payload_json' => $payload,
            'payload_entity_name' => 'payment_link',
            'payload_entity_id' => $paymentLinkId,
            'gateway_transaction_id' => isset($data['transactionId']) ? (string) $data['transactionId'] : null,
            'merchant_reference_id' => $paymentLinkId,
            'channel' => 'MOBILE',
            'amount' => isset($data['paidAmount']) ? $this->centsToDollars((int) $data['paidAmount']) : null,
            'response_code' => isset($data['paid']) ? ((bool) $data['paid'] ? 'paid' : 'unpaid') : null,
            'processing_status' => self::STATUS_RECEIVED,
            'received_at' => now(),
        ]);

        $authorized = $this->basicAuthIsValid($request);
        $webhook->forceFill([
            'signature_valid' => $authorized,
        ])->save();

        if (!$authorized) {
            $this->markFailed($webhook, self::STATUS_INVALID_AUTH, 'Strictly Zero webhook Basic Auth validation failed.');

            return ['duplicate' => false, 'webhook' => $webhook, 'authorized' => false];
        }

        try {
            $this->process($webhook, $payload);

            return ['duplicate' => false, 'webhook' => $webhook, 'authorized' => true];
        } catch (\Throwable $exception) {
            $this->markFailed($webhook, self::STATUS_FAILED, $exception->getMessage());

            return ['duplicate' => false, 'webhook' => $webhook, 'authorized' => true];
        }
    }

    private function process(PaymentGatewayWebhook $webhook, array $payload): void
    {
        if ($webhook->event_type !== 'paymentLink') {
            $webhook->forceFill([
                'processing_status' => self::STATUS_IGNORED,
                'processing_error' => 'Strictly Zero event is not paymentLink.',
                'processed_at' => now(),
            ])->save();

            return;
        }

        $paymentLinkId = $webhook->payload_entity_id;
        if (!$paymentLinkId) {
            $this->markFailed($webhook, self::STATUS_UNMATCHED, 'Strictly Zero webhook did not include paymentLinkId.');

            return;
        }

        $intent = PaymentIntent::query()
            ->where('provider', self::PROVIDER)
            ->where('provider_payment_link_id', $paymentLinkId)
            ->first();

        if (!$intent) {
            $this->markFailed($webhook, self::STATUS_UNMATCHED, 'No PaymentIntent matched Strictly Zero paymentLinkId.');

            return;
        }

        $webhook->forceFill([
            'order_id' => $intent->order_id,
            'payment_installment_id' => $intent->payment_type === 'quota' ? $intent->payment_id : null,
            'order_payment_id' => in_array($intent->payment_type, ['change-order', 'city-fee'], true) ? $intent->payment_id : null,
            'matched_by' => $paymentLinkId,
            'processing_status' => self::STATUS_MATCHED,
        ])->save();

        $link = $this->paymentLinkService->get($paymentLinkId);
        $intent->forceFill([
            'provider_status' => isset($link['paid']) && (bool) $link['paid'] ? 'paid' : 'unpaid',
            'provider_metadata' => array_merge((array) ($intent->provider_metadata ?? []), [
                'last_lookup' => $link,
                'last_webhook' => $payload,
            ]),
        ])->save();

        if (!$this->isPaidInFull($link, $intent)) {
            $webhook->forceFill([
                'processing_status' => self::STATUS_INCOMPLETE,
                'processing_error' => 'Strictly Zero payment link is not paid in full.',
                'processed_at' => now(),
            ])->save();

            return;
        }

        $this->applyBusinessUpdate($webhook, $intent, $link);
    }

    private function applyBusinessUpdate(PaymentGatewayWebhook $webhook, PaymentIntent $intent, array $link): void
    {
        DB::transaction(function () use ($webhook, $intent, $link) {
            if ($this->isRepeatedGatewayTransaction($webhook)) {
                $webhook->forceFill([
                    'processing_status' => self::STATUS_PROCESSED,
                    'processing_error' => 'Gateway transaction already processed for this record.',
                    'processed_at' => now(),
                ])->save();

                return;
            }

            if ($intent->payment_type === 'quota') {
                $this->applyInstallmentPayment($webhook, $intent, $link);
            } elseif (in_array($intent->payment_type, ['change-order', 'city-fee'], true)) {
                $this->applyOrderPayment($webhook, $intent, $link);
            } else {
                throw new RuntimeException("Unsupported PaymentIntent payment type [{$intent->payment_type}].");
            }

            $intent->forceFill([
                'status' => 'PROCESSED',
                'used_at' => now(),
                'provider_status' => 'paid',
            ])->save();

            $webhook->forceFill([
                'processing_status' => self::STATUS_PROCESSED,
                'processing_error' => null,
                'processed_at' => now(),
            ])->save();
        });
    }

    private function applyInstallmentPayment(PaymentGatewayWebhook $webhook, PaymentIntent $intent, array $link): void
    {
        $installment = PaymentInstallment::query()
            ->with('schedule.order')
            ->findOrFail($intent->payment_id);

        $amount = round((float) $intent->amount, 2);
        if ($amount <= 0) {
            throw new RuntimeException('PaymentIntent amount is invalid for installment payment.');
        }

        $movement = $installment->movements()->create([
            'amount' => $amount,
            'paid_at' => now(),
            'paid_by' => null,
            'method' => 'STRICTLY_ZERO',
            'note' => sprintf(
                '[STRICTLY_ZERO] paymentLinkId=%s paymentId=%s transactionId=%s notificationId=%s',
                $webhook->payload_entity_id ?: 'n/a',
                $link['paymentId'] ?? 'n/a',
                $webhook->gateway_transaction_id ?: 'n/a',
                $webhook->notification_id
            ),
        ]);

        $installment->syncPaymentState();

        $order = $installment->schedule?->order;
        if ($order) {
            OrderFinancialEventLogger::log(
                $order,
                'STRICTLY_ZERO_INSTALLMENT_PAID',
                "Strictly Zero payment received for installment '{$installment->label}'",
                [
                    'payment_installment_id' => $installment->id,
                    'payment_intent_id' => $intent->id,
                    'movement_id' => $movement->id,
                    'amount' => $amount,
                    'payment_link_id' => $webhook->payload_entity_id,
                    'gateway_transaction_id' => $webhook->gateway_transaction_id,
                    'notification_id' => $webhook->notification_id,
                ]
            );
        }
    }

    private function applyOrderPayment(PaymentGatewayWebhook $webhook, PaymentIntent $intent, array $link): void
    {
        $orderPayment = OrderPayment::query()
            ->with('order')
            ->findOrFail($intent->payment_id);

        if (strtoupper((string) $orderPayment->status) !== 'PAID') {
            $orderPayment->forceFill([
                'status' => 'PAID',
                'paid_at' => now(),
                'paid_by_id' => null,
                'note' => trim(sprintf(
                    "%s\n[STRICTLY_ZERO] paymentLinkId=%s paymentId=%s transactionId=%s notificationId=%s",
                    (string) ($orderPayment->note ?? ''),
                    $webhook->payload_entity_id ?: 'n/a',
                    $link['paymentId'] ?? 'n/a',
                    $webhook->gateway_transaction_id ?: 'n/a',
                    $webhook->notification_id
                )),
            ])->save();
        }

        if ($orderPayment->order) {
            OrderFinancialEventLogger::log(
                $orderPayment->order,
                'STRICTLY_ZERO_ORDER_PAYMENT_PAID',
                "Strictly Zero payment received for {$orderPayment->type}",
                [
                    'order_payment_id' => $orderPayment->id,
                    'payment_intent_id' => $intent->id,
                    'amount' => (float) $orderPayment->amount,
                    'payment_link_id' => $webhook->payload_entity_id,
                    'gateway_transaction_id' => $webhook->gateway_transaction_id,
                    'notification_id' => $webhook->notification_id,
                ]
            );
        }
    }

    private function isPaidInFull(array $link, PaymentIntent $intent): bool
    {
        $expectedAmount = round((float) $intent->amount, 2);
        $paidAmount = $this->strictlyAmountToDollars($link['paidAmount'] ?? null, $expectedAmount);
        $totalAmount = $this->strictlyAmountToDollars($link['totalAmount'] ?? null, $expectedAmount);

        return (bool) ($link['paid'] ?? false)
            && isset($link['paidAmount'], $link['totalAmount'])
            && $paidAmount >= $totalAmount
            && $paidAmount >= $expectedAmount
            && $totalAmount >= $expectedAmount;
    }

    private function strictlyAmountToDollars(mixed $amount, float $expectedAmount): float
    {
        if (!is_numeric($amount)) {
            return 0.0;
        }

        $amount = (float) $amount;
        $expectedCents = $this->paymentLinkService->amountToCents($expectedAmount);

        if ($expectedCents > 0 && $amount >= $expectedCents) {
            return round($amount / 100, 2);
        }

        return round($amount, 2);
    }

    private function isRepeatedGatewayTransaction(PaymentGatewayWebhook $webhook): bool
    {
        if (!$webhook->gateway_transaction_id) {
            return false;
        }

        $query = PaymentGatewayWebhook::query()
            ->where('id', '!=', $webhook->id)
            ->where('provider', self::PROVIDER)
            ->where('processing_status', self::STATUS_PROCESSED)
            ->where('gateway_transaction_id', $webhook->gateway_transaction_id);

        if ($webhook->payment_installment_id) {
            $query->where('payment_installment_id', $webhook->payment_installment_id);
        }

        if ($webhook->order_payment_id) {
            $query->where('order_payment_id', $webhook->order_payment_id);
        }

        return $query->exists();
    }

    private function basicAuthIsValid(Request $request): bool
    {
        $username = trim((string) config('strictly_zero.webhook_username'));
        $password = trim((string) config('strictly_zero.webhook_password'));

        if ($username === '' && $password === '') {
            return true;
        }

        return hash_equals($username, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword());
    }

    private function notificationId(array $payload, string $rawBody): string
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $parts = array_filter([
            'strictly-zero',
            $payload['action'] ?? null,
            $data['paymentLinkId'] ?? null,
            $data['paymentId'] ?? null,
            $data['transactionId'] ?? null,
        ]);

        if (count($parts) > 1) {
            return implode('-', array_map(fn ($part) => (string) $part, $parts));
        }

        return 'strictly-zero-' . sha1($rawBody . microtime(true));
    }

    private function centsToDollars(int $amount): float
    {
        return round($amount / 100, 2);
    }

    private function markFailed(PaymentGatewayWebhook $webhook, string $status, string $message): void
    {
        $webhook->forceFill([
            'processing_status' => $status,
            'processing_error' => $message,
            'processed_at' => now(),
        ])->save();
    }

    private function headersForStorage(Request $request): array
    {
        $headers = $request->headers->all();
        foreach (['authorization', 'php-auth-pw'] as $header) {
            if (array_key_exists($header, $headers)) {
                $headers[$header] = ['[redacted]'];
            }
        }

        return $headers;
    }
}
