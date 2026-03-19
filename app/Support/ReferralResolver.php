<?php

namespace App\Support;

use App\Enum\ContactSourceEnum;
use App\Enum\RoleEnum;
use App\Enum\StatusUserEnum;
use App\Models\Client;
use App\Models\Referral;
use App\Models\User;

class ReferralResolver
{
    public function resolve(array $attributes): ?Referral
    {
        $source = $this->normalizeString($attributes['source'] ?? null);

        if (! $this->isReferralSource($source)) {
            return null;
        }

        $existingReferralId = $this->normalizeInt($attributes['referral_id'] ?? null);
        if ($existingReferralId) {
            $existingReferral = Referral::query()
                ->whereKey($existingReferralId)
                ->where('type', $source)
                ->first();

            if ($existingReferral) {
                return $existingReferral;
            }
        }

        if ($source === ContactSourceEnum::EXTERNAL_REFERAL->value) {
            $referrerClientId = $this->normalizeInt($attributes['referrer_client_id'] ?? null);
            if ($referrerClientId) {
                $referrerClient = Client::query()->find($referrerClientId);
                if ($referrerClient) {
                    return Referral::updateOrCreate(
                        [
                            'type' => $source,
                            'client_id' => $referrerClient->id,
                        ],
                        [
                            'name' => $referrerClient->name,
                            'phone' => $referrerClient->phone,
                            'email' => $referrerClient->email,
                            'user_id' => null,
                        ]
                    );
                }
            }
        }

        if ($source === ContactSourceEnum::INTERNAL_REFERAL->value) {
            $referrerUserId = $this->normalizeInt($attributes['referrer_user_id'] ?? null);
            if ($referrerUserId) {
                $referrerUser = User::query()
                    ->whereKey($referrerUserId)
                    ->where('status', StatusUserEnum::ACTIVE->value)
                    ->whereDoesntHave('roles', function ($query) {
                        $query->where('name', RoleEnum::CUSTOMER->value);
                    })
                    ->first();

                if ($referrerUser) {
                    return Referral::updateOrCreate(
                        [
                            'type' => $source,
                            'user_id' => $referrerUser->id,
                        ],
                        [
                            'name' => $referrerUser->name,
                            'phone' => $referrerUser->phone,
                            'email' => $referrerUser->email,
                            'client_id' => null,
                        ]
                    );
                }
            }
        }

        $manualName = $this->normalizeString($attributes['refer_name'] ?? null);
        $manualPhone = $this->normalizeString($attributes['refer_phone'] ?? null);
        $manualEmail = $this->normalizeString($attributes['refer_email'] ?? null);

        if ($manualName === null && $manualPhone === null && $manualEmail === null) {
            return null;
        }

        return Referral::firstOrCreate([
            'type' => $source,
            'name' => $manualName,
            'phone' => $manualPhone,
            'email' => $manualEmail,
            'client_id' => null,
            'user_id' => null,
        ]);
    }

    private function isReferralSource(?string $source): bool
    {
        return in_array($source, [
            ContactSourceEnum::EXTERNAL_REFERAL->value,
            ContactSourceEnum::INTERNAL_REFERAL->value,
            ContactSourceEnum::ESW_REFER->value,
            ContactSourceEnum::ESR_REFER->value,
        ], true);
    }

    private function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
