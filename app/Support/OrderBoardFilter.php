<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class OrderBoardFilter
{
    private const AMOUNT_OPERATORS = ['=', '!=', '<', '<=', '>', '>=', 'between'];
    private const DATE_OPERATORS = [
        'on',
        'before',
        'after',
        'between',
        'today',
        'yesterday',
        'this_week',
        'this_month',
        'this_year',
        'last_week',
        'last_month',
    ];
    private const TIMEZONE = 'America/New_York';

    public static function apply(Builder $query, array $filters): Builder
    {
        $field = $filters['filter_field'] ?? null;
        if (!is_string($field) || trim($field) === '') {
            return $query;
        }

        $field = trim($field);
        $value = $filters['filter_value'] ?? null;

        switch ($field) {
            case 'name':
                if (!self::hasText($value)) {
                    break;
                }
                $like = self::likeValue($value);
                $query->where(function ($query) use ($like) {
                    $query->where('orders.name', 'like', $like)
                        ->orWhere('orders.job_address', 'like', $like);
                });
                break;
            case 'name_and_job_address':
                $nameValue = $filters['filter_value'] ?? null;
                $addressValue = $filters['filter_value_secondary'] ?? null;
                if (!self::hasText($nameValue) && !self::hasText($addressValue)) {
                    break;
                }
                if (self::hasText($nameValue)) {
                    $query->where('orders.name', 'like', self::likeValue($nameValue));
                }
                if (self::hasText($addressValue)) {
                    $query->where('orders.job_address', 'like', self::likeValue($addressValue));
                }
                break;
            case 'job_address':
                self::applyLike($query, 'orders.job_address', $value);
                break;
            case 'job_city':
                self::applyLike($query, 'orders.job_city', $value);
                break;
            case 'status':
                if (self::hasText($value) && strtolower(trim((string) $value)) !== 'all') {
                    $query->where('orders.status', $value);
                }
                break;
            case 'city':
                self::applyLike($query, 'orders.city', $value);
                break;
            case 'job_state':
                self::applyLike($query, 'orders.job_state', $value);
                break;
            case 'job_zip':
                self::applyLike($query, 'orders.job_zip', $value);
                break;
            case 'order_type':
                if (self::hasText($value) && strtolower(trim((string) $value)) !== 'all') {
                    $query->where('orders.order_type', $value);
                }
                break;
            case 'is_supply':
                $boolValue = self::parseBoolean($value);
                if ($boolValue !== null) {
                    $query->where('orders.is_supply', $boolValue);
                }
                break;
            case 'owner':
                $ownerId = self::parseId($value);
                if ($ownerId !== null) {
                    $query->whereHas('owners', function ($query) use ($ownerId) {
                        $query->where('users.id', $ownerId);
                    });
                }
                break;
            case 'source':
                if (self::hasText($value) && strtolower(trim((string) $value)) !== 'all') {
                    $query->whereHas('client', function ($query) use ($value) {
                        $query->where('source', $value);
                    });
                }
                break;
            case 'company_name':
                if (self::hasText($value)) {
                    $like = self::likeValue($value);
                    $query->whereHas('client.companyContact', function ($query) use ($like) {
                        $query->where('name', 'like', $like);
                    });
                }
                break;
            case 'client_name':
                if (self::hasText($value)) {
                    $like = self::likeValue($value);
                    $query->whereHas('client', function ($query) use ($like) {
                        $query->where('name', 'like', $like);
                    });
                }
                break;
            case 'phone':
                if (self::hasText($value)) {
                    $like = self::likeValue($value);
                    $query->whereHas('client', function ($query) use ($like) {
                        $query->where('phone', 'like', $like);
                    });
                }
                break;
            case 'tag':
                $tagId = self::parseId($value);
                if ($tagId !== null) {
                    $query->whereHas('tags', function ($query) use ($tagId) {
                        $query->where('tags.id', $tagId);
                    });
                }
                break;
            case 'supervisor':
                $supervisorId = self::parseId($value);
                if ($supervisorId !== null) {
                    $query->where('orders.supervisor_id', $supervisorId);
                }
                break;
            case 'created_by':
                $creatorId = self::parseId($value);
                if ($creatorId !== null) {
                    $query->where('orders.user_id', $creatorId);
                }
                break;
            case 'created_time':
                self::applyCreatedTimeFilter($query, $filters);
                break;
            case 'project_amount':
                self::applyAmountFilter($query, $filters);
                break;
            default:
                break;
        }

        return $query;
    }

    public static function applyMultiple(Builder $query, array $filters, string $match = 'and'): Builder
    {
        $match = strtolower($match) === 'or' ? 'or' : 'and';
        $filters = array_slice($filters, 0, 5);
        $normalized = array_map([self::class, 'normalizeFilter'], $filters);
        $activeFilters = array_values(array_filter($normalized, [self::class, 'isFilterActive']));
        if (count($activeFilters) === 0) {
            return $query;
        }

        if ($match === 'or') {
            $query->where(function ($query) use ($normalized) {
                foreach ($normalized as $filter) {
                    if (!self::isFilterActive($filter)) {
                        continue;
                    }
                    $query->orWhere(function ($subQuery) use ($filter) {
                        self::apply($subQuery, $filter);
                    });
                }
            });
            return $query;
        }

        foreach ($normalized as $filter) {
            if (!self::isFilterActive($filter)) {
                continue;
            }
            self::apply($query, $filter);
        }

        return $query;
    }

    private static function applyLike(Builder $query, string $column, $value): void
    {
        if (!self::hasText($value)) {
            return;
        }
        $query->where($column, 'like', self::likeValue($value));
    }

    private static function applyAmountFilter(Builder $query, array $filters): void
    {
        $operator = $filters['filter_op'] ?? '=';
        if (!in_array($operator, self::AMOUNT_OPERATORS, true)) {
            $operator = '=';
        }

        if ($operator === 'between') {
            $min = self::parseNumber($filters['filter_value_min'] ?? null);
            $max = self::parseNumber($filters['filter_value_max'] ?? null);
            if ($min === null || $max === null) {
                return;
            }
            $query->whereBetween('orders.project_amount', [$min, $max]);
            return;
        }

        $value = self::parseNumber($filters['filter_value'] ?? null);
        if ($value === null) {
            return;
        }

        $query->where('orders.project_amount', $operator, $value);
    }

    private static function applyCreatedTimeFilter(Builder $query, array $filters): void
    {
        $operator = $filters['filter_op'] ?? 'today';
        if (!in_array($operator, self::DATE_OPERATORS, true)) {
            $operator = 'today';
        }

        $now = Carbon::now(self::TIMEZONE);
        switch ($operator) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                $query->whereBetween('orders.created_at', [$start, $end]);
                return;
            case 'yesterday':
                $start = $now->copy()->subDay()->startOfDay();
                $end = $now->copy()->subDay()->endOfDay();
                $query->whereBetween('orders.created_at', [$start, $end]);
                return;
            case 'this_week':
                $start = $now->copy()->startOfWeek(Carbon::MONDAY);
                $end = $now->copy()->endOfWeek(Carbon::MONDAY);
                $query->whereBetween('orders.created_at', [$start, $end]);
                return;
            case 'last_week':
                $start = $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY);
                $end = $now->copy()->subWeek()->endOfWeek(Carbon::MONDAY);
                $query->whereBetween('orders.created_at', [$start, $end]);
                return;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $query->whereBetween('orders.created_at', [$start, $end]);
                return;
            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                $query->whereBetween('orders.created_at', [$start, $end]);
                return;
            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                $query->whereBetween('orders.created_at', [$start, $end]);
                return;
            case 'on':
                $date = self::parseDate($filters['filter_value'] ?? null);
                if ($date === null) {
                    return;
                }
                $query->whereBetween('orders.created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
                return;
            case 'before':
                $date = self::parseDate($filters['filter_value'] ?? null);
                if ($date === null) {
                    return;
                }
                $query->where('orders.created_at', '<=', $date->copy()->endOfDay());
                return;
            case 'after':
                $date = self::parseDate($filters['filter_value'] ?? null);
                if ($date === null) {
                    return;
                }
                $query->where('orders.created_at', '>=', $date->copy()->startOfDay());
                return;
            case 'between':
                $startDate = self::parseDate($filters['filter_value_min'] ?? null);
                $endDate = self::parseDate($filters['filter_value_max'] ?? null);
                if ($startDate === null || $endDate === null) {
                    return;
                }
                $query->whereBetween('orders.created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);
                return;
        }
    }

    private static function hasText($value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private static function normalizeFilter(array $filter): array
    {
        return [
            'filter_field' => $filter['field'] ?? ($filter['filter_field'] ?? null),
            'filter_value' => $filter['value'] ?? ($filter['filter_value'] ?? null),
            'filter_value_secondary' => $filter['value_secondary'] ?? ($filter['filter_value_secondary'] ?? null),
            'filter_op' => $filter['op'] ?? ($filter['filter_op'] ?? null),
            'filter_value_min' => $filter['value_min'] ?? ($filter['filter_value_min'] ?? null),
            'filter_value_max' => $filter['value_max'] ?? ($filter['filter_value_max'] ?? null),
        ];
    }

    private static function isFilterActive(array $filter): bool
    {
        $field = $filter['filter_field'] ?? null;
        if (!is_string($field) || trim($field) === '') {
            return false;
        }

        if ($field === 'created_time') {
            return true;
        }

        if ($field === 'project_amount') {
            return self::hasText($filter['filter_value'] ?? null)
                || self::hasText($filter['filter_value_min'] ?? null)
                || self::hasText($filter['filter_value_max'] ?? null);
        }

        if ($field === 'name_and_job_address') {
            return self::hasText($filter['filter_value'] ?? null)
                || self::hasText($filter['filter_value_secondary'] ?? null);
        }

        return self::hasText($filter['filter_value'] ?? null);
    }

    private static function likeValue(string $value): string
    {
        return '%' . trim($value) . '%';
    }

    private static function parseId($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return is_numeric($value) ? (int) $value : null;
    }

    private static function parseNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return is_numeric($value) ? (float) $value : null;
    }

    private static function parseDate($value): ?Carbon
    {
        if (!self::hasText($value)) {
            return null;
        }
        $value = trim((string) $value);
        try {
            return Carbon::createFromFormat('Y-m-d', $value, self::TIMEZONE);
        } catch (\Exception $exception) {
            try {
                return Carbon::parse($value, self::TIMEZONE);
            } catch (\Exception $exception) {
                return null;
            }
        }
    }

    private static function parseBoolean($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $normalized = strtolower(trim($value));
        if (in_array($normalized, ['1', 'true'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false'], true)) {
            return false;
        }
        return null;
    }
}
