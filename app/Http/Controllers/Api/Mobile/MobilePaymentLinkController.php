<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enum\RoleEnum;
use App\Exceptions\StrictlyZeroPaymentNotPayableException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Support\StrictlyZeroPaymentLinkService;
use App\Support\StrictlyZeroPaymentResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class MobilePaymentLinkController extends Controller
{
    public function __construct(
        private readonly StrictlyZeroPaymentResolver $paymentResolver,
        private readonly StrictlyZeroPaymentLinkService $paymentLinkService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(RoleEnum::CUSTOMER->value)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $validated = $request->validate([
            'payment_type' => ['required', 'in:quota,change-order'],
            'payment_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $payment = $this->paymentResolver->resolve(
                $validated['payment_type'],
                (int) $validated['payment_id'],
                'mobile'
            );
        } catch (StrictlyZeroPaymentNotPayableException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }

        /** @var Order $order */
        $order = $payment['order'];

        if ((int) $order->client?->mobile_user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        $intent = PaymentIntent::create([
            'token' => Str::random(64),
            'payment_type' => $validated['payment_type'],
            'payment_id' => (int) $validated['payment_id'],
            'order_id' => $order->id,
            'amount' => (float) $payment['amount'],
            'channel' => 'MOBILE',
            'provider' => 'STRICTLY_ZERO',
            'status' => 'PENDING',
            'expires_at' => now()->addYears(10),
            'created_by_user_id' => $user->id,
        ]);

        $intent->forceFill([
            'provider_reference' => sprintf('MOB-%d-%s', $intent->id, Str::upper(Str::random(8))),
        ])->save();

        try {
            $strictlyZeroLink = $this->paymentLinkService->create($payment, $intent);
        } catch (Throwable $exception) {
            $intent->forceFill([
                'status' => 'FAILED',
                'provider_status' => 'CREATE_FAILED',
                'provider_metadata' => [
                    'error' => $exception->getMessage(),
                ],
            ])->save();

            return response()->json([
                'message' => 'Unable to create payment link.',
            ], 502);
        }

        $intent->forceFill([
            'provider_payment_link_id' => isset($strictlyZeroLink['id']) ? (string) $strictlyZeroLink['id'] : null,
            'provider_payment_request_id' => isset($strictlyZeroLink['paymentRequestId']) ? (string) $strictlyZeroLink['paymentRequestId'] : null,
            'provider_payment_url' => (string) $strictlyZeroLink['paymentLink'],
            'provider_status' => isset($strictlyZeroLink['status']) ? (string) $strictlyZeroLink['status'] : null,
            'provider_metadata' => $strictlyZeroLink,
        ])->save();

        return response()->json([
            'data' => [
                'payment_url' => $intent->provider_payment_url,
                'expires_at' => null,
                'payment_type' => $intent->payment_type,
                'payment_id' => $intent->payment_id,
                'amount' => (float) $intent->amount,
                'channel' => $intent->channel,
                'provider' => $intent->provider,
            ],
        ]);
    }
}
