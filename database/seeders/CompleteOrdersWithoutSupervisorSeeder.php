<?php

namespace Database\Seeders;

use App\Enum\OrderStatusEnum;
use App\Enum\ServiceEnum;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompleteOrdersWithoutSupervisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startDate = '2025-01-01';
        $endDate = '2025-12-31';

        $orderIds = DB::table('orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.supervisor_id')
            ->join('order_status as os', 'os.order_id', '=', 'o.id')
            ->where('os.status', OrderStatusEnum::CONFIRMED->value)
            ->where('os.created_at', '>=', $startDate)
            ->where('os.created_at', '<', $endDate)
            ->whereIn('o.service', [
                ServiceEnum::DELIVERY->value,
                ServiceEnum::PICKUP->value,
            ])
            ->where(function ($query) {
                $query->whereNull('o.supervisor_id')
                    ->orWhereNull('u.id');
            })
            ->distinct()
            ->pluck('o.id');

        if ($orderIds->isEmpty()) {
            return;
        }

        $rangeEnd = Carbon::parse($endDate)->subDay()->endOfDay();
        $rangeEndDate = $rangeEnd->toDateString();

        DB::table('orders')
            ->whereIn('id', $orderIds)
            ->update([
                'status' => OrderStatusEnum::COMPLETE->value,
                'complete_date' => $rangeEndDate,
            ]);

        $existingCompleteOrderIds = DB::table('order_status')
            ->whereIn('order_id', $orderIds)
            ->where('status', OrderStatusEnum::COMPLETE->value)
            ->pluck('order_id')
            ->all();

        if (!empty($existingCompleteOrderIds)) {
            DB::table('order_status')
                ->whereIn('order_id', $existingCompleteOrderIds)
                ->where('status', OrderStatusEnum::COMPLETE->value)
                ->update([
                    'complete_date' => $rangeEndDate,
                    'created_at' => $rangeEnd,
                    'updated_at' => $rangeEnd,
                ]);
        }

        $existingCompleteLookup = array_flip($existingCompleteOrderIds);

        $orderRows = DB::table('orders')
            ->select('id', 'user_id')
            ->whereIn('id', $orderIds)
            ->get();

        $statusRows = [];
        foreach ($orderRows as $order) {
            if (isset($existingCompleteLookup[$order->id])) {
                continue;
            }

            $statusRows[] = [
                'status' => OrderStatusEnum::COMPLETE->value,
                'notes' => 'COMPLETE updated by seeder',
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'complete_date' => $rangeEndDate,
                'created_at' => $rangeEnd,
                'updated_at' => $rangeEnd,
            ];
        }

        if (!empty($statusRows)) {
            DB::table('order_status')->insert($statusRows);
        }
    }
}
