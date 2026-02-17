<?php

namespace Database\Seeders;

use App\Enum\PaymentInstallmentStatusEnum;
use App\Models\PaymentInstallment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackfillPaymentInstallmentMovementsSeeder extends Seeder
{
    public function run(): void
    {
        $query = PaymentInstallment::query()
            ->whereIn('status', [
                PaymentInstallmentStatusEnum::PAID->value,
                PaymentInstallmentStatusEnum::OVERPAID->value,
            ])
            ->whereDoesntHave('movements')
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->command?->info('No legacy installments found for backfill.');
            return;
        }

        $this->command?->info("Installments to process: {$total}");

        $created = 0;
        $skipped = 0;

        $query->chunkById(200, function ($installments) use (&$created, &$skipped) {
            foreach ($installments as $installment) {
                $amount = round((float) $installment->amount, 2);
                if ($amount <= 0) {
                    $skipped++;
                    continue;
                }

                DB::transaction(function () use ($installment, $amount, &$created) {
                    $installment->movements()->create([
                        'amount' => $amount,
                        'paid_at' => $installment->paid_at ?? $installment->created_at ?? now(),
                        'paid_by' => $installment->paid_by,
                        'method' => 'LEGACY_BACKFILL',
                        'note' => 'Backfill legacy paid installment',
                    ]);

                    $installment->syncPaymentState();
                    $created++;
                });
            }
        });

        $this->command?->info("Backfill completed. Created: {$created}. Skipped: {$skipped}.");
    }
}
