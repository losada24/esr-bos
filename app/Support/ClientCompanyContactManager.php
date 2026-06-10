<?php

namespace App\Support;

use App\Models\Client;
use App\Models\ClientCompanyContact;
use App\Models\CompanyContact;
use Illuminate\Support\Facades\DB;

class ClientCompanyContactManager
{
    public function attach(Client $client, ?int $companyId, bool $setAsPrimary = false): void
    {
        $companyIds = $this->sanitizeCompanyIds([$companyId]);
        if (empty($companyIds)) {
            return;
        }

        $companyId = $companyIds[0];

        $existingLink = ClientCompanyContact::withTrashed()
            ->where('client_id', $client->id)
            ->where('company_contact_id', $companyId)
            ->first();

        if ($existingLink) {
            $existingLink->is_primary = false;
            $existingLink->deleted_by_user_id = null;
            $existingLink->save();

            if ($existingLink->trashed()) {
                $existingLink->restore();
            }
        } else {
            ClientCompanyContact::create([
                'client_id' => $client->id,
                'company_contact_id' => $companyId,
                'is_primary' => false,
            ]);
        }

        $this->ensurePrimaryCompany($client, $setAsPrimary ? $companyId : null);
    }

    public function sync(Client $client, array $companyIds, ?int $preferredPrimaryCompanyId = null): void
    {
        $companyIds = $this->sanitizeCompanyIds($companyIds);

        $activeCompanyIds = ClientCompanyContact::query()
            ->where('client_id', $client->id)
            ->pluck('company_contact_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $companyIdsToDetach = collect($activeCompanyIds)
            ->reject(fn (int $activeCompanyId) => in_array($activeCompanyId, $companyIds, true))
            ->values()
            ->all();

        foreach ($companyIdsToDetach as $companyIdToDetach) {
            $this->detach($client, $companyIdToDetach);
        }

        foreach ($companyIds as $companyIdToAttach) {
            $this->attach($client, $companyIdToAttach);
        }

        $this->ensurePrimaryCompany($client, $preferredPrimaryCompanyId);
    }

    public function detach(Client $client, ?int $companyId): void
    {
        $companyIds = $this->sanitizeCompanyIds([$companyId]);
        if (empty($companyIds)) {
            return;
        }

        $companyId = $companyIds[0];

        $link = ClientCompanyContact::query()
            ->where('client_id', $client->id)
            ->where('company_contact_id', $companyId)
            ->first();

        if (!$link) {
            return;
        }

        $link->is_primary = false;
        $link->deleted_by_user_id = auth()->id();
        $link->save();
        $link->delete();

        $this->ensurePrimaryCompany($client);
    }

    protected function ensurePrimaryCompany(Client $client, ?int $preferredPrimaryCompanyId = null): void
    {
        $companyIds = ClientCompanyContact::query()
            ->where('client_id', $client->id)
            ->pluck('company_contact_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($companyIds)) {
            ClientCompanyContact::withTrashed()
                ->where('client_id', $client->id)
                ->update([
                    'is_primary' => false,
                    'updated_at' => now(),
                ]);

            if ($client->company_contact_id !== null) {
                $client->update(['company_contact_id' => null]);
            }

            $client->unsetRelation('companyContacts');

            return;
        }

        $currentPrimaryId = $client->company_contact_id ? (int) $client->company_contact_id : null;

        if ($preferredPrimaryCompanyId && in_array($preferredPrimaryCompanyId, $companyIds, true)) {
            $primaryCompanyId = $preferredPrimaryCompanyId;
        } elseif ($currentPrimaryId && in_array($currentPrimaryId, $companyIds, true)) {
            $primaryCompanyId = $currentPrimaryId;
        } else {
            $primaryCompanyId = $companyIds[0];
        }

        ClientCompanyContact::query()
            ->where('client_id', $client->id)
            ->update([
                'is_primary' => false,
                'updated_at' => now(),
            ]);

        ClientCompanyContact::query()
            ->where('client_id', $client->id)
            ->where('company_contact_id', $primaryCompanyId)
            ->update([
                'is_primary' => true,
                'updated_at' => now(),
            ]);

        if ((int) $client->company_contact_id !== $primaryCompanyId) {
            $client->update(['company_contact_id' => $primaryCompanyId]);
        }

        $client->unsetRelation('companyContacts');
    }

    /**
     * @param  array<int|null>  $companyIds
     * @return array<int>
     */
    protected function sanitizeCompanyIds(array $companyIds): array
    {
        $normalizedCompanyIds = collect($companyIds)
            ->filter(fn ($id) => !empty($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($normalizedCompanyIds)) {
            return [];
        }

        return CompanyContact::query()
            ->whereIn('id', $normalizedCompanyIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
