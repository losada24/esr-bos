<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthorizeNetPaymentNotPayableException;
use App\Support\AuthorizeNetGateway;
use App\Support\AuthorizeNetPaymentResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        try {
            $payment = $this->paymentResolver->resolve($paymentType, $paymentId, $channel);
        } catch (AuthorizeNetPaymentNotPayableException $exception) {
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
            'message' => 'The payment was submitted to Authorize.Net. Your system will mark it as paid after the webhook is processed.',
            'reference' => $request->query('reference'),
        ]);
    }

    public function cancel(Request $request): View
    {
        return view('payments.authorize-net-status', [
            'title' => 'Payment Cancelled',
            'message' => 'The payment form was cancelled before the transaction completed.',
            'reference' => $request->query('reference'),
        ]);
    }
}
