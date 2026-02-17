<?php

namespace App\Console\Commands;

use App\Enum\PaymentInstallmentStatusEnum;
use App\Models\PaymentInstallment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPaymentInstallmentMovements extends Command
{
    protected $signature = 'payments:backfill-installment-movements
        {--dry-run : Show how many installments would be backfilled without writing data}
        {--chunk=200 : Batch size for processing installments}';

    protected $description = 'Create initial movement records for legacy paid installments without movements';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $query = PaymentInstallment::query()
            ->whereIn('status', [
                PaymentInstallmentStatusEnum::PAID->value,
                PaymentInstallmentStatusEnum::OVERPAID->value,
            ])
            ->whereDoesntHave('movements')
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No legacy installments found for backfill.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[Dry-run] ' : '') . "Installments to process: {$total}");

        $created = 0;
        $skipped = 0;

        $query->chunkById($chunk, function ($installments) use ($dryRun, &$created, &$skipped) {
            foreach ($installments as $installment) {
                $amount = round((float) $installment->amount, 2);
                if ($amount <= 0) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $created++;
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

        $this->info("Backfill completed. Created: {$created}. Skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
