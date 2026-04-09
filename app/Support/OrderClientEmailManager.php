<?php

namespace App\Support;

use App\Models\Client;
use App\Models\CompanyContact;
use App\Models\Order;
use App\Models\OrderCompanyContact;
use Illuminate\Support\Collection;

class OrderClientEmailManager
{
    public const PRIMARY_SELECTION = '__PRIMARY__';
    public const NONE_SELECTION = '__NONE__';

    public function resolveRecipient(Order $order): ?string
    {
        if ((bool) $order->do_not_send_email) {
            return null;
        }

        $override = $this->normalizeEmail($order->client_email_override);
        if ($override !== null) {
            return $override;
        }

        [$client] = $this->resolveContextForOrder($order);

        return $this->normalizeEmail(optional($client)->email);
    }

    public function selectionForOrder(Order $order): string
    {
        if ((bool) $order->do_not_send_email) {
            return self::NONE_SELECTION;
        }

        return $this->normalizeEmail($order->client_email_override) ?? self::PRIMARY_SELECTION;
    }

    public function optionsForOrder(Order $order, ?OrderCompanyContact $contextContact = null): array
    {
        [$client, $companyContact] = $contextContact
            ? [$contextContact->client, $contextContact->companyContact]
            : $this->resolveContextForOrder($order);

        return $this->optionsForContext($client, $companyContact);
    }

    public function optionsForContext(?Client $client, ?CompanyContact $companyContact = null): array
    {
        $options = [];
        $client?->loadMissing('companyContacts');

        $this->pushOption(
            $options,
            $client?->email,
            'Primary client email',
            true
        );

        $this->pushOption(
            $options,
            $client?->secondary_email,
            'Secondary client email'
        );

        if ($companyContact) {
            $name = trim((string) $companyContact->name);
            $label = $name !== ''
                ? "Selected company email: {$name}"
                : 'Selected company email';

            $this->pushOption($options, $companyContact->email, $label);
        }

        /** @var Collection<int, CompanyContact> $companyContacts */
        $companyContacts = $client?->companyContacts ?? collect();
        foreach ($companyContacts as $linkedCompanyContact) {
            $name = trim((string) $linkedCompanyContact->name);
            $label = $name !== ''
                ? "Associated company email: {$name}"
                : 'Associated company email';

            $this->pushOption($options, $linkedCompanyContact->email, $label);
        }

        return array_values($options);
    }

    public function validateSelection(Order $order, string $selection, ?OrderCompanyContact $contextContact = null): ?string
    {
        $normalizedSelection = trim($selection);
        if ($normalizedSelection === '') {
            return 'Select how client emails should be handled for this order.';
        }

        if ($normalizedSelection === self::NONE_SELECTION) {
            return null;
        }

        return $this->validateSelectionAgainstOptions(
            $this->optionsForOrder($order, $contextContact),
            $normalizedSelection
        );
    }

    public function validateSelectionForContext(?Client $client, string $selection, ?CompanyContact $companyContact = null): ?string
    {
        $normalizedSelection = trim($selection);
        if ($normalizedSelection === '') {
            return 'Select how client emails should be handled for this order.';
        }

        if ($normalizedSelection === self::NONE_SELECTION) {
            return null;
        }

        return $this->validateSelectionAgainstOptions(
            $this->optionsForContext($client, $companyContact),
            $normalizedSelection
        );
    }

    public function applySelection(Order $order, string $selection): void
    {
        $normalizedSelection = trim($selection);

        if ($normalizedSelection === self::NONE_SELECTION) {
            $order->do_not_send_email = true;
            $order->client_email_override = null;

            return;
        }

        $order->do_not_send_email = false;

        if ($normalizedSelection === self::PRIMARY_SELECTION || $normalizedSelection === '') {
            $order->client_email_override = null;

            return;
        }

        $order->client_email_override = $this->normalizeEmail($normalizedSelection);
    }

    private function validateSelectionAgainstOptions(array $options, string $normalizedSelection): ?string
    {
        $optionsCollection = collect($options);
        $primaryOption = $optionsCollection->firstWhere('is_primary', true);

        if ($normalizedSelection === self::PRIMARY_SELECTION) {
            if (!$primaryOption || empty($primaryOption['value'])) {
                return 'Primary client email is not available. Select another associated email or choose not to send client emails.';
            }

            return null;
        }

        $allowedEmails = $optionsCollection
            ->pluck('value')
            ->filter(fn ($email) => is_string($email) && trim($email) !== '')
            ->map(fn ($email) => mb_strtolower(trim($email)))
            ->values();

        if (!$allowedEmails->contains(mb_strtolower($normalizedSelection))) {
            return 'The selected client email is not available for this order.';
        }

        return null;
    }

    private function resolveContextForOrder(Order $order): array
    {
        $order->loadMissing([
            'client.companyContacts',
            'orderCompanyContacts.client.companyContacts',
            'orderCompanyContacts.companyContact',
        ]);

        $contextContact = $order->orderCompanyContacts
            ->firstWhere('is_selected', true)
            ?? $order->orderCompanyContacts->first();

        return [
            $order->client ?? $contextContact?->client,
            $contextContact?->companyContact,
        ];
    }

    private function pushOption(array &$options, ?string $email, string $label, bool $isPrimary = false): void
    {
        $normalizedEmail = $this->normalizeEmail($email);
        if ($normalizedEmail === null) {
            return;
        }

        $key = mb_strtolower($normalizedEmail);

        if (isset($options[$key])) {
            if ($isPrimary) {
                $options[$key]['is_primary'] = true;
                $options[$key]['label'] = "{$label}: {$normalizedEmail}";
            }

            return;
        }

        $options[$key] = [
            'value' => $normalizedEmail,
            'label' => "{$label}: {$normalizedEmail}",
            'is_primary' => $isPrimary,
        ];
    }

    private function normalizeEmail(?string $email): ?string
    {
        if (!is_string($email)) {
            return null;
        }

        $normalizedEmail = trim($email);

        return $normalizedEmail !== '' ? $normalizedEmail : null;
    }
}
