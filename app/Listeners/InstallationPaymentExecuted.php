<?php

namespace App\Listeners;

use App\Enum\InstallerPaymentStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Events\PaymentCreated;

class InstallationPaymentExecuted
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCreated $event): void
    {
        $totalPrice = $event->installationPayment->order->getGrandTotalPrice();
        $installationPayment = (float)$event->installationPayment->order->installationPayments()
            ->where('payment_status', PaymentStatusEnum::PAID->value)
            ->sum('installer_payment');
        $status = $event->installationPayment->payment_status;

        // dd($totalPrice, $installationPayment, $status);

        if ($installationPayment >= $totalPrice && $event->installationPayment->payment_status === PaymentStatusEnum::PAID->value) {
            $event->installationPayment->order->paymentExtraFields()->update([
                'installer_payment_status' => InstallerPaymentStatusEnum::FULLY_PAID->value,
            ]);
        } else if ($event->installationPayment->payment_status === PaymentStatusEnum::PAID->value) {
            $event->installationPayment->order->paymentExtraFields()->update([
                'installer_payment_status' => InstallerPaymentStatusEnum::PARTIALLY_PAID->value,
            ]);
        }
    }
}
