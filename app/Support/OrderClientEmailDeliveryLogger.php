<?php

namespace App\Support;

use App\Models\Order;

class OrderClientEmailDeliveryLogger
{
    public function __construct(
        protected OrderClientEmailManager $orderClientEmailManager
    ) {
    }

    public function capture(Order $order): array
    {
        return [
            'selection' => $this->orderClientEmailManager->selectionForOrder($order),
            'recipient' => $this->normalize($this->orderClientEmailManager->resolveRecipient($order)),
        ];
    }

    public function logIfChanged(Order $order, array $beforeState): void
    {
        $afterState = $this->capture($order);

        if (
            ($beforeState['selection'] ?? null) === ($afterState['selection'] ?? null)
            && ($beforeState['recipient'] ?? null) === ($afterState['recipient'] ?? null)
        ) {
            return;
        }

        OrderFinancialEventLogger::log(
            $order,
            'CLIENT_EMAIL_DELIVERY_UPDATED',
            'Client email delivery updated',
            [
                'before_delivery' => $this->describeState($beforeState),
                'after_delivery' => $this->describeState($afterState),
                'before_selection' => $beforeState['selection'] ?? null,
                'after_selection' => $afterState['selection'] ?? null,
                'before_recipient' => $beforeState['recipient'] ?? null,
                'after_recipient' => $afterState['recipient'] ?? null,
            ]
        );
    }

    public function logIfConfiguredDifferentlyFromDefault(Order $order, ?string $defaultPrimaryRecipient): void
    {
        $defaultState = [
            'selection' => OrderClientEmailManager::PRIMARY_SELECTION,
            'recipient' => $this->normalize($defaultPrimaryRecipient),
        ];
        $afterState = $this->capture($order);

        if (
            $defaultState['selection'] === $afterState['selection']
            && $defaultState['recipient'] === $afterState['recipient']
        ) {
            return;
        }

        OrderFinancialEventLogger::log(
            $order,
            'CLIENT_EMAIL_DELIVERY_UPDATED',
            'Client email delivery configured',
            [
                'before_delivery' => $this->describeState($defaultState),
                'after_delivery' => $this->describeState($afterState),
                'before_selection' => $defaultState['selection'],
                'after_selection' => $afterState['selection'] ?? null,
                'before_recipient' => $defaultState['recipient'],
                'after_recipient' => $afterState['recipient'] ?? null,
            ]
        );
    }

    private function describeState(array $state): string
    {
        $selection = (string) ($state['selection'] ?? OrderClientEmailManager::PRIMARY_SELECTION);
        $recipient = $this->normalize($state['recipient'] ?? null);

        if ($selection === OrderClientEmailManager::NONE_SELECTION) {
            return 'Do not send client emails';
        }

        if ($selection === OrderClientEmailManager::PRIMARY_SELECTION) {
            return $recipient !== null
                ? "Primary client email ({$recipient})"
                : 'Primary client email';
        }

        return $recipient ?? $selection;
    }

    private function normalize(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }
}
