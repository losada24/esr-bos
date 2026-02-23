<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Validation\ValidationException;

class QualifiedOrderDuplicateChecker
{
    public const ERROR_KEY = 'duplicate_order_confirmation';

    public const DEFAULT_MESSAGE = 'Existe una orden con este mismo nombre y el mismo cliente asociado. ¿Desea crearla de todas formas?';

    public function findDuplicate(?string $orderName, ?int $clientId, ?int $ignoreOrderId = null): ?Order
    {
        $normalizedName = trim((string) $orderName);
        if ($normalizedName === '' || !$clientId) {
            return null;
        }

        return Order::query()
            ->where(function ($query) use ($clientId) {
                $query->where('client_id', $clientId)
                    ->orWhereHas('orderCompanyContacts', function ($companyContactsQuery) use ($clientId) {
                        $companyContactsQuery->where('client_id', $clientId);
                    });
            })
            ->when($ignoreOrderId, fn ($query) => $query->where('id', '!=', $ignoreOrderId))
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($normalizedName)])
            ->orderByDesc('id')
            ->first();
    }

    public function ensureNoDuplicateUnlessForced(
        ?string $orderName,
        ?int $clientId,
        bool $forceDuplicate = false,
        ?int $ignoreOrderId = null
    ): void {
        if ($forceDuplicate) {
            return;
        }

        $duplicate = $this->findDuplicate($orderName, $clientId, $ignoreOrderId);
        if (!$duplicate) {
            return;
        }

        throw ValidationException::withMessages([
            self::ERROR_KEY => self::DEFAULT_MESSAGE,
        ]);
    }
}
