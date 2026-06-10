<?php

namespace App\Support;

class AuthorizeNetPaymentReference
{
    public static function buildQuota(int $installmentId, string $channel = 'web'): string
    {
        return self::compose($channel, 'inst', $installmentId);
    }

    public static function buildChangeOrder(int $orderPaymentId, string $channel = 'web'): string
    {
        return self::compose($channel, 'op', $orderPaymentId);
    }

    public static function parse(?string $reference): ?array
    {
        $reference = trim((string) $reference);
        if ($reference === '') {
            return null;
        }

        if (!preg_match('/^(?<channel>[a-z0-9]+)_(?<kind>inst|op)_(?<id>\d+)$/i', $reference, $matches)) {
            return null;
        }

        return [
            'channel' => strtolower($matches['channel']),
            'kind' => strtolower($matches['kind']),
            'id' => (int) $matches['id'],
        ];
    }

    private static function compose(string $channel, string $kind, int $id): string
    {
        $channel = strtolower(trim($channel));

        return sprintf('%s_%s_%d', $channel, $kind, $id);
    }
}
