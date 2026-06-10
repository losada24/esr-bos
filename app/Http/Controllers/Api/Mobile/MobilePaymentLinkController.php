<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enum\RoleEnum;
use App\Exceptions\AuthorizeNetPaymentNotPayableException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Support\AuthorizeNetPaymentResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MobilePaymentLinkController extends Controller
{
    public function __construct(
        private readonly AuthorizeNetPaymentResolver $paymentResolver
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
        } catch (AuthorizeNetPaymentNotPayableException $exception) {
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
            'status' => 'PENDING',
            'expires_at' => now()->addMinutes(config('authorize_net.payment_intent_ttl_minutes')),
            'created_by_user_id' => $user->id,
        ]);

        return response()->json([
            'data' => [
                'payment_url' => route('authorize-net.payments.intent.show', ['token' => $intent->token]),
                'expires_at' => optional($intent->expires_at)->toISOString(),
                'payment_type' => $intent->payment_type,
                'payment_id' => $intent->payment_id,
                'amount' => (float) $intent->amount,
                'channel' => $intent->channel,
            ],
        ]);
    }
}
