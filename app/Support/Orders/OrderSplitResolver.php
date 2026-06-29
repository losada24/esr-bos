<?php

namespace App\Support\Orders;

use App\Enum\CommissionBeneficiaryRelationEnum;
use App\Enum\CommissionStatusEnum;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class OrderSplitResolver
{
    public function resolve(?int $parentOrderId, ?Order $order = null): array
    {
        if (! $parentOrderId) {
            return [
                'parent_order_id' => null,
                'root_order_id' => null,
                'counts_for_owner_commission' => true,
            ];
        }

        if ($order && (int) $order->id === (int) $parentOrderId) {
            throw ValidationException::withMessages([
                'parent_order_id' => 'An order cannot be linked as its own parent.',
            ]);
        }

        $parent = Order::query()
            ->select('id', 'parent_order_id', 'root_order_id')
            ->findOrFail($parentOrderId);

        if ($order) {
            $this->ensureNoCycle($parent, $order);
            $this->ensureOwnerCommissionsCanBeExcluded($order);
        }

        return [
            'parent_order_id' => $parent->id,
            'root_order_id' => $parent->root_order_id ?: $parent->id,
            'counts_for_owner_commission' => false,
        ];
    }

    private function ensureNoCycle(Order $parent, Order $order): void
    {
        $current = $parent;
        $visited = [];

        while ($current) {
            if ((int) $current->id === (int) $order->id) {
                throw ValidationException::withMessages([
                    'parent_order_id' => 'This parent order would create a circular split relationship.',
                ]);
            }

            if (isset($visited[$current->id]) || ! $current->parent_order_id) {
                break;
            }

            $visited[$current->id] = true;
            $current = Order::query()
                ->select('id', 'parent_order_id', 'root_order_id')
                ->find($current->parent_order_id);
        }
    }

    private function ensureOwnerCommissionsCanBeExcluded(Order $order): void
    {
        $hasActiveOwnerCommission = $order->orderCommissions()
            ->where('beneficiary_relation', CommissionBeneficiaryRelationEnum::OWNER->value)
            ->where('status', '!=', CommissionStatusEnum::CANCELED->value)
            ->exists();

        if ($hasActiveOwnerCommission) {
            throw ValidationException::withMessages([
                'parent_order_id' => 'This order has an active owner commission. Cancel or remove that commission before linking it to a parent order.',
            ]);
        }
    }
}
