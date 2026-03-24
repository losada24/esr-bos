<?php

namespace App\Support;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AuthorizeNetGateway
{
    public function requestHostedPaymentToken(array $payment): array
    {
        $returnOptions = [
            'showReceipt' => false,
            'url' => route('authorize-net.payments.complete', ['reference' => $payment['reference']]),
            'urlText' => 'Continue',
            'cancelUrl' => route('authorize-net.payments.cancel', ['reference' => $payment['reference']]),
            'cancelUrlText' => 'Cancel',
        ];

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(20)
            ->post(config('authorize_net.api_url'), [
                'getHostedPaymentPageRequest' => [
                    'merchantAuthentication' => [
                        'name' => (string) config('authorize_net.api_login_id'),
                        'transactionKey' => (string) config('authorize_net.transaction_key'),
                    ],
                    'refId' => $payment['reference'],
                    'transactionRequest' => [
                        'transactionType' => 'authCaptureTransaction',
                        'amount' => $payment['amount'],
                        'order' => [
                            'invoiceNumber' => $payment['invoice_number'],
                            'description' => $payment['description'],
                        ],
                    ],
                    'hostedPaymentSettings' => [
                        'setting' => [
                            [
                                'settingName' => 'hostedPaymentReturnOptions',
                                'settingValue' => json_encode($returnOptions, JSON_UNESCAPED_SLASHES),
                            ],
                            [
                                'settingName' => 'hostedPaymentButtonOptions',
                                'settingValue' => json_encode([
                                    'text' => 'Pay',
                                ], JSON_UNESCAPED_SLASHES),
                            ],
                            [
                                'settingName' => 'hostedPaymentOrderOptions',
                                'settingValue' => json_encode([
                                    'show' => true,
                                ], JSON_UNESCAPED_SLASHES),
                            ],
                            [
                                'settingName' => 'hostedPaymentBillingAddressOptions',
                                'settingValue' => json_encode([
                                    'show' => true,
                                    'required' => false,
                                ], JSON_UNESCAPED_SLASHES),
                            ],
                            [
                                'settingName' => 'hostedPaymentPaymentOptions',
                                'settingValue' => json_encode([
                                    'showCreditCard' => true,
                                    'showBankAccount' => false,
                                    'cardCodeRequired' => true,
                                ], JSON_UNESCAPED_SLASHES),
                            ],
                        ],
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            Log::error('Authorize.Net hosted payment token HTTP error.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Authorize.Net hosted payment token request failed.', previous: $exception);
        }

        $body = $response->json();
        $payload = $body['getHostedPaymentPageResponse'] ?? $body;
        $messages = $payload['messages']['message'] ?? [];
        $resultCode = $payload['messages']['resultCode'] ?? null;
        $token = $payload['token'] ?? null;

        if ($resultCode !== 'Ok' || !is_string($token) || $token === '') {
            $message = collect($messages)
                ->map(fn ($item) => $item['text'] ?? null)
                ->filter()
                ->implode(' ');

            Log::error('Authorize.Net hosted payment token error response.', [
                'request_reference' => $payment['reference'] ?? null,
                'request_amount' => $payment['amount'] ?? null,
                'request_invoice_number' => $payment['invoice_number'] ?? null,
                'return_options' => $returnOptions,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
                'decoded_response' => $body,
            ]);

            throw new RuntimeException($message !== '' ? $message : 'Authorize.Net did not return a valid hosted payment token.');
        }

        return [
            'token' => $token,
            'response' => $payload,
        ];
    }
}
