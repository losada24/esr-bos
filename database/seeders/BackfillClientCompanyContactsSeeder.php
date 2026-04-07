<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackfillClientCompanyContactsSeeder extends Seeder
{
    public function run(): void
    {
        $query = DB::table('clients')
            ->select('id', 'company_contact_id')
            ->whereNull('deleted_at')
            ->whereNotNull('company_contact_id')
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->command?->info('No clients with legacy company assignments found.');
            return;
        }

        $this->command?->info("Legacy client-company links to process: {$total}");

        $processed = 0;
        $createdOrUpdated = 0;

        $query->chunkById(500, function ($clients) use (&$processed, &$createdOrUpdated, $total) {
            $rows = [];
            $now = now();

            foreach ($clients as $client) {
                if (empty($client->company_contact_id)) {
                    continue;
                }

                $rows[] = [
                    'client_id' => $client->id,
                    'company_contact_id' => $client->company_contact_id,
                    'is_primary' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($rows)) {
                DB::table('client_company_contacts')->upsert(
                    $rows,
                    ['client_id', 'company_contact_id'],
                    ['is_primary', 'updated_at']
                );

                $createdOrUpdated += count($rows);
            }

            $processed += count($clients);
            $this->command?->info("Processed {$processed} / {$total} legacy client rows.");
        });

        $this->command?->info("Backfill completed. Links created or updated: {$createdOrUpdated}.");
    }
}
