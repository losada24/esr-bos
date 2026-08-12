<?php

namespace App\Support;

use App\Models\PaymentIntent;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StrictlyZeroPaymentLinkService
{
    public function create(array $payment, PaymentIntent $intent): array
    {
        $amountInCents = $this->amountToCents((float) $payment['amount']);

        $payload = [
            'amount' => $amountInCents,
            'customValues' => [
                [
                    'key' => 'payment_reference',
                    'label' => 'Payment Reference',
                    'value' => (string) $intent->provider_reference,
                ],
                [
                    'key' => 'payment_intent_id',
                    'label' => 'Payment Intent ID',
                    'value' => (string) $intent->id,
                ],
                [
                    'key' => 'payment_type',
                    'label' => 'Payment Type',
                    'value' => (string) $intent->payment_type,
                ],
                [
                    'key' => 'payment_id',
                    'label' => 'Payment ID',
                    'value' => (string) $intent->payment_id,
                ],
                [
                    'key' => 'order_id',
                    'label' => 'Order ID',
                    'value' => (string) $intent->order_id,
                ],
            ],
            'showBilling' => true,
            'showShipping' => false,
        ];

        $response = Http::withHeaders([
            'key-hash' => $this->requiredConfig('key_hash'),
            'Accept' => 'application/json',
        ])
            ->withBasicAuth($this->requiredConfig('username'), $this->requiredConfig('password'))
            ->post($this->url(), $payload);

        if (!$response->successful()) {
            throw new RuntimeException(sprintf(
                'Strictly Zero payment link request failed with status [%s]: %s',
                $response->status(),
                $response->body()
            ));
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new RuntimeException('Strictly Zero payment link response was not valid JSON.');
        }

        return [
            ...$data,
            'paymentLink' => $this->normalizePaymentUrl((string) ($data['paymentLink'] ?? '')),
            'request_payload' => $payload,
        ];
    }

    public function get(string $paymentLinkId): array
    {
        $response = Http::withHeaders([
            'key-hash' => $this->requiredConfig('key_hash'),
            'Accept' => 'application/json',
        ])
            ->withBasicAuth($this->requiredConfig('username'), $this->requiredConfig('password'))
            ->get($this->url() . '/' . rawurlencode($paymentLinkId));

        if (!$response->successful()) {
            throw new RuntimeException(sprintf(
                'Strictly Zero payment link lookup failed with status [%s]: %s',
                $response->status(),
                $response->body()
            ));
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('Strictly Zero payment link lookup response was not valid JSON.');
        }

        $data = $json['data'] ?? $json;
        if (!is_array($data)) {
            throw new RuntimeException('Strictly Zero payment link lookup response did not include data.');
        }

        return $data;
    }

    public function amountToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function url(): string
    {
        return rtrim((string) config('strictly_zero.base_url'), '/') . '/' . ltrim((string) config('strictly_zero.payment_link_path'), '/');
    }

    private function requiredConfig(string $key): string
    {
        $value = trim((string) config('strictly_zero.' . $key));
        if ($value === '') {
            throw new RuntimeException("Strictly Zero config [{$key}] is missing.");
        }

        return $value;
    }

    private function normalizePaymentUrl(string $paymentUrl): string
    {
        $paymentUrl = trim($paymentUrl);
        if ($paymentUrl === '') {
            throw new RuntimeException('Strictly Zero response did not include a paymentLink.');
        }

        if (str_starts_with($paymentUrl, '//')) {
            return 'https:' . $paymentUrl;
        }

        if (str_starts_with($paymentUrl, '.')) {
            return 'https://merchant' . $paymentUrl;
        }

        if (!preg_match('/^https?:\/\//i', $paymentUrl)) {
            return 'https://' . $paymentUrl;
        }

        return $paymentUrl;
    }
}
