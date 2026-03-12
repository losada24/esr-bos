<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderPipelineSort
{
    public const SORT_BY_ORDER_OWNER = 'order_owner';
    public const SORT_BY_ORDER_NAME = 'order_name';
    public const SORT_BY_JOB_SITE = 'job_site';
    public const SORT_BY_COMPANY_NAME = 'company_name';
    public const SORT_BY_CONTACT_NAME = 'contact_name';
    public const SORT_BY_AMOUNT = 'amount';
    public const SORT_BY_CREATED_BY = 'created_by';
    public const SORT_BY_CREATED_TIME = 'created_time';
    public const SORT_BY_MODIFIED_TIME = 'modified_time';

    public const SORT_DIR_ASC = 'asc';
    public const SORT_DIR_DESC = 'desc';

    public const DEFAULT_SORT_BY = self::SORT_BY_MODIFIED_TIME;
    public const DEFAULT_SORT_DIR = self::SORT_DIR_ASC;

    /**
     * @return array{sort_by: string, sort_dir: string}
     */
    public static function resolveFromRequest(Request $request): array
    {
        return [
            'sort_by' => self::normalizeSortBy((string) $request->query('sort_by', self::DEFAULT_SORT_BY)),
            'sort_dir' => self::normalizeSortDir((string) $request->query('sort_dir', self::DEFAULT_SORT_DIR)),
        ];
    }

    public static function normalizeSortBy(string $sortBy): string
    {
        $allowed = self::allowedSortByValues();
        return in_array($sortBy, $allowed, true) ? $sortBy : self::DEFAULT_SORT_BY;
    }

    public static function normalizeSortDir(string $sortDir): string
    {
        $normalized = strtolower($sortDir);
        return in_array($normalized, [self::SORT_DIR_ASC, self::SORT_DIR_DESC], true)
            ? $normalized
            : self::DEFAULT_SORT_DIR;
    }

    public static function apply(Builder $query, string $sortBy, string $sortDir): Builder
    {
        $sortBy = self::normalizeSortBy($sortBy);
        $sortDir = self::normalizeSortDir($sortDir);

        switch ($sortBy) {
            case self::SORT_BY_ORDER_OWNER:
                $query->orderByRaw(self::orderOwnerExpression() . " {$sortDir}");
                break;
            case self::SORT_BY_ORDER_NAME:
                $query->orderByRaw("LOWER(COALESCE(orders.name, '')) {$sortDir}");
                break;
            case self::SORT_BY_JOB_SITE:
                $query->orderByRaw("LOWER(COALESCE(orders.job_address, '')) {$sortDir}");
                break;
            case self::SORT_BY_COMPANY_NAME:
                $query->orderByRaw(self::companyNameExpression() . " {$sortDir}");
                break;
            case self::SORT_BY_CONTACT_NAME:
                $query->orderByRaw(self::contactNameExpression() . " {$sortDir}");
                break;
            case self::SORT_BY_AMOUNT:
                $query->orderByRaw("COALESCE(orders.project_amount, 0) {$sortDir}");
                break;
            case self::SORT_BY_CREATED_BY:
                $query->orderByRaw(self::createdByExpression() . " {$sortDir}");
                break;
            case self::SORT_BY_CREATED_TIME:
                $query->orderBy('orders.created_at', $sortDir);
                break;
            case self::SORT_BY_MODIFIED_TIME:
            default:
                $query->orderBy('orders.updated_at', $sortDir);
                break;
        }

        return $query->orderBy('orders.id', $sortDir);
    }

    /**
     * @return array<int, string>
     */
    private static function allowedSortByValues(): array
    {
        return [
            self::SORT_BY_ORDER_OWNER,
            self::SORT_BY_ORDER_NAME,
            self::SORT_BY_JOB_SITE,
            self::SORT_BY_COMPANY_NAME,
            self::SORT_BY_CONTACT_NAME,
            self::SORT_BY_AMOUNT,
            self::SORT_BY_CREATED_BY,
            self::SORT_BY_CREATED_TIME,
            self::SORT_BY_MODIFIED_TIME,
        ];
    }

    private static function orderOwnerExpression(): string
    {
        return <<<SQL
LOWER(COALESCE((
    SELECT MIN(u.name)
    FROM owner_user ou
    INNER JOIN users u ON u.id = ou.user_id
    WHERE ou.order_id = orders.id
), ''))
SQL;
    }

    private static function companyNameExpression(): string
    {
        return <<<SQL
LOWER(COALESCE(
    (
        SELECT cc.name
        FROM order_company_contacts occ
        INNER JOIN company_contacts cc ON cc.id = occ.company_contact_id
        WHERE occ.order_id = orders.id
        ORDER BY occ.is_selected DESC, occ.id ASC
        LIMIT 1
    ),
    (
        SELECT cc2.name
        FROM clients c
        INNER JOIN company_contacts cc2 ON cc2.id = c.company_contact_id
        WHERE c.id = orders.client_id
        LIMIT 1
    ),
    ''
))
SQL;
    }

    private static function contactNameExpression(): string
    {
        return <<<SQL
LOWER(COALESCE(
    (
        SELECT c.name
        FROM order_company_contacts occ
        INNER JOIN clients c ON c.id = occ.client_id
        WHERE occ.order_id = orders.id
        ORDER BY occ.is_selected DESC, occ.id ASC
        LIMIT 1
    ),
    (
        SELECT c2.name
        FROM clients c2
        WHERE c2.id = orders.client_id
        LIMIT 1
    ),
    ''
))
SQL;
    }

    private static function createdByExpression(): string
    {
        return <<<SQL
LOWER(COALESCE((
    SELECT u.name
    FROM users u
    WHERE u.id = orders.user_id
    LIMIT 1
), ''))
SQL;
    }
}
