<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Validation\ValidationException;

class QualifiedOrderDuplicateChecker
{
    public const ERROR_KEY = 'duplicate_order_confirmation';

    public const DEFAULT_MESSAGE = 'An order already exists with the same name and associated client. Do you want to create it anyway?';
    public const JOB_ADDRESS_DUPLICATE_MESSAGE = 'An order already exists with the same job address. Do you want to create it anyway?';

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

    public function findDuplicateByJobAddress(
        ?string $jobAddress,
        ?int $ignoreOrderId = null,
        ?string $city = null,
        ?string $jobZip = null
    ): ?Order {
        $normalizedJobAddress = trim((string) $jobAddress);
        if ($normalizedJobAddress === '') {
            return null;
        }

        $query = Order::query()
            ->when($ignoreOrderId, fn ($query) => $query->where('id', '!=', $ignoreOrderId))
            ->whereRaw('LOWER(TRIM(job_address)) = ?', [mb_strtolower($normalizedJobAddress)]);

        $normalizedCity = trim((string) $city);
        if ($normalizedCity !== '') {
            $query->whereRaw('LOWER(TRIM(city)) = ?', [mb_strtolower($normalizedCity)]);
        }

        $normalizedJobZip = trim((string) $jobZip);
        if ($normalizedJobZip !== '') {
            $query->whereRaw('LOWER(TRIM(job_zip)) = ?', [mb_strtolower($normalizedJobZip)]);
        }

        return $query
            ->orderByDesc('id')
            ->first();
    }

    public function ensureNoDuplicateUnlessForced(
        ?string $orderName,
        ?int $clientId,
        bool $forceDuplicate = false,
        ?int $ignoreOrderId = null,
        ?string $jobAddress = null,
        ?string $city = null,
        ?string $jobZip = null
    ): void {
        if ($forceDuplicate) {
            return;
        }

        $duplicate = $this->findDuplicate($orderName, $clientId, $ignoreOrderId);
        if (!$duplicate) {
            $duplicate = $this->findDuplicateByJobAddress($jobAddress, $ignoreOrderId, $city, $jobZip);
            if (!$duplicate) {
                return;
            }

            throw ValidationException::withMessages([
                self::ERROR_KEY => self::JOB_ADDRESS_DUPLICATE_MESSAGE,
            ]);
        }

        throw ValidationException::withMessages([
            self::ERROR_KEY => self::DEFAULT_MESSAGE,
        ]);
    }
}
