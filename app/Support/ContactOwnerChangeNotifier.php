<?php

namespace App\Support;

use App\Enum\RoleEnum;
use App\Jobs\SendGmailEmail;
use App\Mail\ContactOwnerAssigned;
use App\Models\Client;
use App\Models\CrmNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ContactOwnerChangeNotifier
{
    public function notify(
        Client $client,
        ?int $previousOwnerId,
        ?int $currentOwnerId,
        bool $isNewContact = false,
    ): void {
        if (!$isNewContact && $previousOwnerId === $currentOwnerId) {
            return;
        }

        $dispatchNotifications = function () use ($client, $currentOwnerId, $isNewContact): void {
            $freshClient = Client::query()->find($client->id);

            if (!$freshClient) {
                return;
            }

            $recipients = User::role(RoleEnum::OWNER_ADMIN->value)
                ->get(['users.id', 'users.name', 'users.email']);

            if ($currentOwnerId) {
                $assignedOwner = User::query()
                    ->select('id', 'name', 'email')
                    ->find($currentOwnerId);

                if ($assignedOwner) {
                    $recipients->push($assignedOwner);
                }
            }

            $recipients = $recipients
                ->unique('id')
                ->values();

            $sentEmails = [];

            foreach ($recipients as $recipient) {
                CrmNotification::create([
                    'user_id' => $recipient->id,
                    'actor_id' => auth()->id(),
                    'type' => CrmNotification::TYPE_FEED,
                    'title' => $isNewContact ? 'New contact assigned' : 'Contact owner updated',
                    'body' => ($freshClient->name ?: ('Contact #' . $freshClient->id))
                        . ' was assigned to '
                        . ($freshClient->user?->name ?: 'an owner'),
                    'data' => [
                        'url' => route('client.edit', $freshClient),
                    ],
                    'notifiable_type' => Client::class,
                    'notifiable_id' => $freshClient->id,
                ]);

                $email = trim((string) $recipient->email);
                $normalizedEmail = mb_strtolower($email);
                if ($email === '' || in_array($normalizedEmail, $sentEmails, true)) {
                    continue;
                }
                $sentEmails[] = $normalizedEmail;

                SendGmailEmail::dispatch(
                    $email,
                    new ContactOwnerAssigned(
                        $freshClient,
                        (string) $recipient->name,
                        $isNewContact
                    )
                )
                    ->onQueue('emails')
                    ->afterCommit();
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatchNotifications);
            return;
        }

        $dispatchNotifications();
    }
}
