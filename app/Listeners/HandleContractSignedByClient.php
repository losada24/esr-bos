<?php

namespace App\Listeners;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Events\OrderStatusChanged;
use App\Jobs\SendGmailEmail;
use App\Mail\MobileAppAccessCreated;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class HandleContractSignedByClient
{
    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        if (!config('custom.mobile_onboarding_enabled')) {
            return;
        }

        if ($event->status !== OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value) {
            return;
        }

        $order = $event->order->loadMissing('client');
        $client = $order->client;
        if (!$client) {
            return;
        }

        $email = is_string($client->email) ? trim($client->email) : '';
        if ($email === '') {
            return;
        }

        $user = User::withTrashed()->where('email', $email)->first();
        $passwordPlain = null;
        $created = false;
        $forcedReset = false;

        if (!$user) {
            $passwordPlain = Str::random(12);
            $user = User::create([
                'name' => $client->name ?? $email,
                'email' => $email,
                'password' => Hash::make($passwordPlain),
            ]);
            $created = true;
        } else {
            if ($user->trashed()) {
                if (!$event->forceCustomerRole && !$user->hasRole(RoleEnum::CUSTOMER->value)) {
                    return;
                }
                $user->restore();
            }

            if (!$user->hasRole(RoleEnum::CUSTOMER->value) && !$event->forceCustomerRole) {
                return;
            }

            if ($event->forceCustomerRole) {
                $passwordPlain = Str::random(12);
                $user->password = Hash::make($passwordPlain);
                $user->save();
                $forcedReset = true;
            }
        }

        Role::firstOrCreate(['name' => RoleEnum::CUSTOMER->value]);

        if ($event->forceCustomerRole) {
            $user->syncRoles([RoleEnum::CUSTOMER->value]);
        } elseif (!$user->hasRole(RoleEnum::CUSTOMER->value)) {
            $user->assignRole(RoleEnum::CUSTOMER->value);
        }

        if ((int) $client->mobile_user_id !== (int) $user->id) {
            $client->mobile_user_id = $user->id;
            $client->save();
        }

        if (($created || $forcedReset) && $passwordPlain !== null) {
            $mailable = new MobileAppAccessCreated(
                $client->name ?? $user->name ?? 'Customer',
                $email,
                $passwordPlain,
                config('custom.mobile_app_store_url'),
                config('custom.mobile_play_store_url')
            );
            SendGmailEmail::dispatch($email, $mailable)->onQueue('emails');
        }
    }
}
