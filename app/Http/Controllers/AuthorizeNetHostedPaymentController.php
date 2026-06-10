<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthorizeNetPaymentNotPayableException;
use App\Models\PaymentIntent;
use App\Support\AuthorizeNetGateway;
use App\Support\AuthorizeNetPaymentResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthorizeNetHostedPaymentController extends Controller
{
    public function __construct(
        private readonly AuthorizeNetPaymentResolver $paymentResolver,
        private readonly AuthorizeNetGateway $gateway
    ) {
    }

    public function show(Request $request, string $paymentType, int $paymentId): View|RedirectResponse
    {
        $channel = (string) $request->query('channel', 'web');

        return $this->renderHostedPayment($paymentType, $paymentId, $channel, null, $request);
    }

    public function showIntent(string $token, Request $request): View|RedirectResponse|Response
    {
        $intent = PaymentIntent::query()
            ->where('token', $token)
            ->firstOrFail();

        if ($intent->used_at !== null || strtoupper((string) $intent->status) !== 'PENDING') {
            return response()->view('payments.authorize-net-status', [
                'title' => 'Payment Link Unavailable',
                'message' => 'This payment link is no longer available. Please return to the app or website and request a new one.',
                'reference' => null,
                'status_variant' => 'neutral',
            ], 410);
        }

        if ($intent->expires_at !== null && $intent->expires_at->isPast()) {
            $intent->forceFill([
                'status' => 'EXPIRED',
            ])->save();

            return response()->view('payments.authorize-net-status', [
                'title' => 'Payment Link Expired',
                'message' => 'This payment link has expired. Please return to the app or website and request a new one.',
                'reference' => null,
                'status_variant' => 'neutral',
            ], 410);
        }

        $view = $this->renderHostedPayment(
            $intent->payment_type,
            (int) $intent->payment_id,
            strtolower((string) $intent->channel),
            $intent,
            $request
        );

        $intent->forceFill([
            'used_at' => now(),
            'status' => 'USED',
        ])->save();

        return $view;
    }

    private function renderHostedPayment(
        string $paymentType,
        int $paymentId,
        string $channel,
        ?PaymentIntent $intent,
        Request $request
    ): View|RedirectResponse|Response {
        try {
            $payment = $this->paymentResolver->resolve($paymentType, $paymentId, $channel);
        } catch (AuthorizeNetPaymentNotPayableException $exception) {
            if ($intent) {
                return response()->view('payments.authorize-net-status', [
                    'title' => 'Payment Link Unavailable',
                    'message' => $exception->getMessage(),
                    'reference' => null,
                    'status_variant' => 'neutral',
                ], 410);
            }

            return back()->with('error', $exception->getMessage());
        }

        $hostedPayment = $this->gateway->requestHostedPaymentToken($payment);

        return view('payments.authorize-net-redirect', [
            'payment' => $payment,
            'token' => $hostedPayment['token'],
            'formUrl' => config('authorize_net.form_url'),
        ]);
    }

    public function complete(Request $request): View
    {
        return view('payments.authorize-net-status', [
            'title' => 'Payment Submitted',
            'message' => 'Your payment request was submitted successfully. You may now return to the app or website.',
            'reference' => $request->query('reference'),
            'status_variant' => 'complete',
        ]);
    }

    public function cancel(Request $request): View
    {
        return view('payments.authorize-net-status', [
            'title' => 'Payment Cancelled',
            'message' => 'Your payment was cancelled before it was completed. You may return to the app or website whenever you are ready.',
            'reference' => $request->query('reference'),
            'status_variant' => 'cancel',
        ]);
    }
}
