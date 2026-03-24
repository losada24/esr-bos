<?php

namespace App\Support;

use App\Models\OrderPayment;
use App\Models\PaymentGatewayWebhook;
use App\Models\PaymentInstallment;
use App\Support\OrderFinancialEventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AuthorizeNetWebhookProcessor
{
    private const PROVIDER = 'AUTHORIZE_NET';
    private const STATUS_RECEIVED = 'RECEIVED';
    private const STATUS_INVALID_SIGNATURE = 'INVALID_SIGNATURE';
    private const STATUS_UNMATCHED = 'UNMATCHED';
    private const STATUS_MATCHED = 'MATCHED';
    private const STATUS_PROCESSED = 'PROCESSED';
    private const STATUS_FAILED = 'FAILED';
    private const PROCESSABLE_EVENT = 'net.authorize.payment.authcapture.created';

    public function handle(Request $request): array
    {
        $rawBody = (string) $request->getContent();
        $payload = json_decode($rawBody, true);
        $payload = is_array($payload) ? $payload : [];

        $notificationId = (string) ($payload['notificationId'] ?? ('missing-' . sha1($rawBody)));
        $existing = PaymentGatewayWebhook::query()
            ->where('notification_id', $notificationId)
            ->first();

        if ($existing) {
            return ['duplicate' => true, 'webhook' => $existing];
        }

        $webhook = PaymentGatewayWebhook::create([
            'provider' => self::PROVIDER,
            'notification_id' => $notificationId,
            'webhook_id' => $payload['webhookId'] ?? null,
            'event_type' => $payload['eventType'] ?? 'unknown',
            'event_date' => $this->parseDate($payload['eventDate'] ?? null),
            'signature_header' => $request->header('X-ANET-Signature'),
            'signature_valid' => false,
            'source_ip' => $request->ip(),
            'headers_json' => $request->headers->all(),
            'raw_body' => $rawBody,
            'payload_json' => $payload,
            'payload_entity_name' => $payload['payload']['entityName'] ?? null,
            'payload_entity_id' => isset($payload['payload']['id']) ? (string) $payload['payload']['id'] : null,
            'gateway_transaction_id' => isset($payload['payload']['id']) ? (string) $payload['payload']['id'] : null,
            'merchant_reference_id' => $payload['payload']['merchantReferenceId'] ?? null,
            'amount' => isset($payload['payload']['authAmount']) ? (float) $payload['payload']['authAmount'] : null,
            'response_code' => isset($payload['payload']['responseCode']) ? (string) $payload['payload']['responseCode'] : null,
            'processing_status' => self::STATUS_RECEIVED,
            'received_at' => now(),
        ]);

        try {
            $signatureValid = $this->signatureIsValid($rawBody, $webhook->signature_header);
            $webhook->forceFill([
                'signature_valid' => $signatureValid,
            ])->save();

            if (!$signatureValid) {
                $this->markFailed($webhook, self::STATUS_INVALID_SIGNATURE, 'Authorize.Net signature validation failed.');

                return ['duplicate' => false, 'webhook' => $webhook];
            }

            $reference = AuthorizeNetPaymentReference::parse($webhook->merchant_reference_id);
            if (!$reference) {
                $this->markFailed($webhook, self::STATUS_UNMATCHED, 'Merchant reference is missing or invalid.');

                return ['duplicate' => false, 'webhook' => $webhook];
            }

            $webhook->forceFill([
                'channel' => strtoupper($reference['channel']),
            ])->save();

            $matched = $this->matchReference($webhook, $reference);
            if (!$matched) {
                $this->markFailed($webhook, self::STATUS_UNMATCHED, 'No matching payment record found for merchant reference.');

                return ['duplicate' => false, 'webhook' => $webhook];
            }

            $webhook->forceFill([
                'processing_status' => self::STATUS_MATCHED,
            ])->save();

            if ($webhook->event_type !== self::PROCESSABLE_EVENT) {
                $webhook->forceFill([
                    'processed_at' => now(),
                ])->save();

                return ['duplicate' => false, 'webhook' => $webhook];
            }

            $this->applyBusinessUpdate($webhook);

            return ['duplicate' => false, 'webhook' => $webhook];
        } catch (\Throwable $exception) {
            $this->markFailed($webhook, self::STATUS_FAILED, $exception->getMessage());

            return ['duplicate' => false, 'webhook' => $webhook];
        }
    }

    private function signatureIsValid(string $rawBody, ?string $signatureHeader): bool
    {
        $signatureHeader = trim((string) $signatureHeader);
        $signatureKey = trim((string) config('authorize_net.signature_key'));

        if ($signatureHeader === '' || $signatureKey === '') {
            return false;
        }

        $expected = ['sha512=' . hash_hmac('sha512', $rawBody, $signatureKey)];
        if (ctype_xdigit($signatureKey) && strlen($signatureKey) % 2 === 0) {
            $decodedKey = hex2bin($signatureKey);
            if ($decodedKey !== false) {
                $expected[] = 'sha512=' . hash_hmac('sha512', $rawBody, $decodedKey);
            }
        }

        foreach ($expected as $candidate) {
            if (hash_equals(strtoupper($candidate), strtoupper($signatureHeader))) {
                return true;
            }
        }

        return false;
    }

    private function matchReference(PaymentGatewayWebhook $webhook, array $reference): bool
    {
        if ($reference['kind'] === 'inst') {
            $installment = PaymentInstallment::query()
                ->with('schedule.order')
                ->find($reference['id']);

            if (!$installment || !$installment->schedule?->order) {
                return false;
            }

            $webhook->forceFill([
                'order_id' => $installment->schedule->order->id,
                'payment_installment_id' => $installment->id,
                'matched_by' => $webhook->merchant_reference_id,
            ])->save();

            return true;
        }

        if ($reference['kind'] === 'op') {
            $orderPayment = OrderPayment::query()
                ->with('order')
                ->find($reference['id']);

            if (!$orderPayment || !$orderPayment->order) {
                return false;
            }

            $webhook->forceFill([
                'order_id' => $orderPayment->order->id,
                'order_payment_id' => $orderPayment->id,
                'matched_by' => $webhook->merchant_reference_id,
            ])->save();

            return true;
        }

        return false;
    }

    private function applyBusinessUpdate(PaymentGatewayWebhook $webhook): void
    {
        DB::transaction(function () use ($webhook) {
            if ($this->isRepeatedGatewayTransaction($webhook)) {
                $webhook->forceFill([
                    'processing_status' => self::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'processing_error' => 'Gateway transaction already processed for this record.',
                ])->save();

                return;
            }

            if ($webhook->payment_installment_id) {
                $this->applyInstallmentPayment($webhook);
            } elseif ($webhook->order_payment_id) {
                $this->applyOrderPayment($webhook);
            } else {
                throw new RuntimeException('Webhook matched no supported payment target.');
            }

            $webhook->forceFill([
                'processing_status' => self::STATUS_PROCESSED,
                'processed_at' => now(),
                'processing_error' => null,
            ])->save();
        });
    }

    private function applyInstallmentPayment(PaymentGatewayWebhook $webhook): void
    {
        $installment = PaymentInstallment::query()
            ->with('schedule.order')
            ->findOrFail($webhook->payment_installment_id);

        $paidAt = $webhook->event_date ?? $webhook->received_at ?? now();
        $amount = round((float) $webhook->amount, 2);
        if ($amount <= 0) {
            throw new RuntimeException('Webhook amount is invalid for installment payment.');
        }

        $movement = $installment->movements()->create([
            'amount' => $amount,
            'paid_at' => $paidAt,
            'paid_by' => null,
            'method' => 'AUTHORIZE_NET',
            'note' => sprintf(
                '[AUTHORIZE.NET] channel=%s transId=%s notificationId=%s',
                $webhook->channel ?: 'UNKNOWN',
                $webhook->gateway_transaction_id ?: 'n/a',
                $webhook->notification_id
            ),
        ]);

        $installment->syncPaymentState();

        $order = $installment->schedule?->order;
        if ($order) {
            OrderFinancialEventLogger::log(
                $order,
                'AUTHORIZE_NET_INSTALLMENT_PAID',
                "Authorize.Net payment received for installment '{$installment->label}'",
                [
                    'payment_installment_id' => $installment->id,
                    'movement_id' => $movement->id,
                    'amount' => $amount,
                    'paid_at' => $paidAt?->toISOString(),
                    'channel' => $webhook->channel,
                    'gateway_transaction_id' => $webhook->gateway_transaction_id,
                    'notification_id' => $webhook->notification_id,
                ]
            );
        }
    }

    private function applyOrderPayment(PaymentGatewayWebhook $webhook): void
    {
        $orderPayment = OrderPayment::query()
            ->with('order')
            ->findOrFail($webhook->order_payment_id);

        if (strtoupper((string) $orderPayment->status) !== 'PAID') {
            $orderPayment->forceFill([
                'status' => 'PAID',
                'paid_at' => $webhook->event_date ?? $webhook->received_at ?? now(),
                'paid_by_id' => null,
                'note' => trim(sprintf(
                    "%s\n[AUTHORIZE.NET] channel=%s transId=%s notificationId=%s",
                    (string) ($orderPayment->note ?? ''),
                    $webhook->channel ?: 'UNKNOWN',
                    $webhook->gateway_transaction_id ?: 'n/a',
                    $webhook->notification_id
                )),
            ])->save();
        }

        if ($orderPayment->order) {
            OrderFinancialEventLogger::log(
                $orderPayment->order,
                'AUTHORIZE_NET_ORDER_PAYMENT_PAID',
                "Authorize.Net payment received for {$orderPayment->type}",
                [
                    'order_payment_id' => $orderPayment->id,
                    'amount' => (float) $orderPayment->amount,
                    'paid_at' => $orderPayment->paid_at?->toISOString(),
                    'channel' => $webhook->channel,
                    'gateway_transaction_id' => $webhook->gateway_transaction_id,
                    'notification_id' => $webhook->notification_id,
                ]
            );
        }
    }

    private function isRepeatedGatewayTransaction(PaymentGatewayWebhook $webhook): bool
    {
        $query = PaymentGatewayWebhook::query()
            ->where('id', '!=', $webhook->id)
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

    private function markFailed(PaymentGatewayWebhook $webhook, string $status, string $message): void
    {
        $webhook->forceFill([
            'processing_status' => $status,
            'processing_error' => $message,
            'processed_at' => now(),
        ])->save();
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }
}
