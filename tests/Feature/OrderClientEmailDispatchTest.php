<?php

use App\Enum\OrderStatusEnum;
use App\Enum\ServiceEnum;
use App\Enum\StatusUserEnum;
use App\Jobs\SendGmailEmail;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use App\Traits\OrderEmails;
use Illuminate\Support\Facades\Bus;

test('a planned order email reaches a client whose address belongs to a deleted system user', function () {
    Bus::fake();

    $clientEmail = 'deleted-user-client@example.com';
    $client = Client::factory()->create(['email' => $clientEmail]);
    $creator = User::factory()->create();

    User::factory()->create([
        'email' => $clientEmail,
        'status' => StatusUserEnum::ACTIVE->value,
        'deleted_at' => now(),
    ]);

    $order = Order::create([
        'client_id' => $client->id,
        'user_id' => $creator->id,
        'name' => 'Planned client email order',
        'order_number' => 'ORD-CLIENT-EMAIL-001',
        'status' => OrderStatusEnum::PLANNED->value,
        'service' => ServiceEnum::INSTALLATION->value,
        'do_not_send_email' => false,
    ]);

    $sender = new class {
        use OrderEmails;
    };

    $sender->sendEmail($order);

    Bus::assertDispatchedTimes(SendGmailEmail::class, 1);
});
