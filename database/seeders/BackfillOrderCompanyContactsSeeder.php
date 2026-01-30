<?php

namespace Database\Seeders;

use App\Enum\OrderTypeEnum;
use App\Models\Order;
use App\Models\OrderCompanyContact;
use App\Models\Source;
use Illuminate\Database\Seeder;

class BackfillOrderCompanyContactsSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSourceId = Source::query()->orderBy('id')->value('id');
        if (!$defaultSourceId) {
            $this->command?->warn('No sources found. Backfill skipped.');
            return;
        }

        Order::query()
            ->where('order_type', OrderTypeEnum::COMMERCIAL->value)
            ->whereDoesntHave('orderCompanyContacts')
            ->with(['client'])
            ->chunkById(200, function ($orders) use ($defaultSourceId) {
                foreach ($orders as $order) {
                    $client = $order->client;
                    $companyContactId = $client?->company_contact_id;
                    if (!$client || !$companyContactId) {
                        continue;
                    }

                    OrderCompanyContact::create([
                        'order_id' => $order->id,
                        'company_contact_id' => $companyContactId,
                        'client_id' => $client->id,
                        'source_id' => $defaultSourceId,
                        'is_selected' => true,
                        'selected_at' => now(),
                    ]);
                }
            });
    }
}
