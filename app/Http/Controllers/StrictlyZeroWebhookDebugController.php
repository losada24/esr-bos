<?php

namespace App\Http\Controllers;

use App\Models\PaymentGatewayWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StrictlyZeroWebhookDebugController extends Controller
{
    public function store(Request $request): Response
    {
        $rawBody = (string) $request->getContent();
        $payload = json_decode($rawBody, true);
        $payload = is_array($payload) ? $payload : [];

        $notificationId = $this->notificationId($payload, $rawBody);
        if (PaymentGatewayWebhook::query()->where('notification_id', $notificationId)->exists()) {
            return response('duplicate', 200);
        }

        PaymentGatewayWebhook::create([
            'provider' => 'STRICTLY_ZERO',
            'notification_id' => $notificationId,
            'webhook_id' => isset($payload['_id']) ? (string) $payload['_id'] : null,
            'event_type' => (string) ($payload['action'] ?? 'unknown'),
            'signature_header' => $request->header('X-Signature') ?? $request->header('X-Webhook-Signature'),
            'signature_valid' => false,
            'source_ip' => $request->ip(),
            'headers_json' => $request->headers->all(),
            'raw_body' => $rawBody,
            'payload_json' => $payload,
            'payload_entity_name' => 'payment_link_debug',
            'payload_entity_id' => isset($payload['data']['paymentId']) ? (string) $payload['data']['paymentId'] : null,
            'gateway_transaction_id' => isset($payload['data']['transactionId']) ? (string) $payload['data']['transactionId'] : null,
            'merchant_reference_id' => $this->customValue($payload, 'payment_reference'),
            'channel' => 'STRICTLY_ZERO_DEBUG',
            'amount' => isset($payload['data']['amount']) ? ((float) $payload['data']['amount'] / 100) : null,
            'response_code' => isset($payload['data']['status']) ? (string) $payload['data']['status'] : null,
            'processing_status' => 'RECEIVED',
            'received_at' => now(),
        ]);

        return response('ok', 200);
    }

    private function notificationId(array $payload, string $rawBody): string
    {
        foreach (['_id', 'id', 'notificationId'] as $key) {
            if (!empty($payload[$key])) {
                return 'strictly-zero-' . (string) $payload[$key];
            }
        }

        return 'strictly-zero-' . sha1($rawBody . microtime(true));
    }

    private function customValue(array $payload, string $key): ?string
    {
        $customValues = $payload['data']['order']['customValues'] ?? [];
        if (!is_array($customValues)) {
            return null;
        }

        foreach ($customValues as $customValue) {
            if (($customValue['key'] ?? null) === $key) {
                return isset($customValue['value']) ? (string) $customValue['value'] : null;
            }
        }

        return null;
    }
}
