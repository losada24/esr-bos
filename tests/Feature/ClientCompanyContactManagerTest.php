<?php

use App\Models\Client;
use App\Models\CompanyContact;
use App\Support\ClientCompanyContactManager;
use Illuminate\Support\Facades\DB;

test('a client can be linked to multiple companies while keeping a primary company', function () {
    $manager = app(ClientCompanyContactManager::class);

    $primaryCompany = CompanyContact::create(['name' => 'Primary Company']);
    $secondaryCompany = CompanyContact::create(['name' => 'Secondary Company']);
    $client = Client::factory()->create([
        'phone' => '3055550001',
        'company_contact_id' => null,
    ]);

    $manager->sync($client, [$primaryCompany->id], $primaryCompany->id);
    $manager->attach($client, $secondaryCompany->id);

    $client->refresh()->load('companyContacts');

    expect($client->company_contact_id)->toBe($primaryCompany->id);
    expect($client->companyContacts->pluck('id')->sort()->values()->all())
        ->toBe([$primaryCompany->id, $secondaryCompany->id]);

    $pivotRows = DB::table('client_company_contacts')
        ->where('client_id', $client->id)
        ->orderBy('company_contact_id')
        ->get(['company_contact_id', 'is_primary']);

    expect($pivotRows)->toHaveCount(2);
    expect((bool) $pivotRows[0]->is_primary)->toBeTrue();
    expect((bool) $pivotRows[1]->is_primary)->toBeFalse();
});

test('detaching the primary company promotes another linked company', function () {
    $manager = app(ClientCompanyContactManager::class);

    $primaryCompany = CompanyContact::create(['name' => 'Primary Company']);
    $secondaryCompany = CompanyContact::create(['name' => 'Secondary Company']);
    $client = Client::factory()->create([
        'phone' => '3055550002',
        'company_contact_id' => null,
    ]);

    $manager->sync($client, [$primaryCompany->id, $secondaryCompany->id], $primaryCompany->id);
    $manager->detach($client, $primaryCompany->id);

    $client->refresh()->load('companyContacts');

    expect($client->company_contact_id)->toBe($secondaryCompany->id);
    expect($client->companyContacts->pluck('id')->all())->toBe([$secondaryCompany->id]);

    $pivotRow = DB::table('client_company_contacts')
        ->where('client_id', $client->id)
        ->where('company_contact_id', $secondaryCompany->id)
        ->first(['is_primary']);

    expect($pivotRow)->not->toBeNull();
    expect((bool) $pivotRow->is_primary)->toBeTrue();

    $deletedPivotRow = DB::table('client_company_contacts')
        ->where('client_id', $client->id)
        ->where('company_contact_id', $primaryCompany->id)
        ->first(['deleted_at', 'deleted_by_user_id', 'is_primary']);

    expect($deletedPivotRow)->not->toBeNull();
    expect($deletedPivotRow->deleted_at)->not->toBeNull();
    expect((bool) $deletedPivotRow->is_primary)->toBeFalse();
});

test('re-attaching a previously deleted link restores the same pivot row', function () {
    $manager = app(ClientCompanyContactManager::class);

    $company = CompanyContact::create(['name' => 'Primary Company']);
    $client = Client::factory()->create([
        'phone' => '3055550003',
        'company_contact_id' => null,
    ]);

    $manager->sync($client, [$company->id], $company->id);
    $manager->detach($client, $company->id);
    $manager->attach($client, $company->id, true);

    $client->refresh()->load('companyContacts');

    expect($client->company_contact_id)->toBe($company->id);
    expect($client->companyContacts->pluck('id')->all())->toBe([$company->id]);

    $pivotRows = DB::table('client_company_contacts')
        ->where('client_id', $client->id)
        ->where('company_contact_id', $company->id)
        ->get(['deleted_at', 'deleted_by_user_id', 'is_primary']);

    expect($pivotRows)->toHaveCount(1);
    expect($pivotRows[0]->deleted_at)->toBeNull();
    expect($pivotRows[0]->deleted_by_user_id)->toBeNull();
    expect((bool) $pivotRows[0]->is_primary)->toBeTrue();
});
