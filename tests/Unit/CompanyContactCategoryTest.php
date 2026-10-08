<?php

use App\Actions\CreateCompanyContact;
use App\Actions\UpdateCompanyContact;
use App\Enum\CompanyCategoryEnum;
use App\Http\Controllers\OrderStorageController;
use App\Models\Client;
use App\Models\CompanyContact;
use App\Models\Order;
use App\Models\OrderCompanyContact;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');

    Schema::create('company_contacts', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('category')->nullable();
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->string('website')->nullable();
        $table->string('billing_street')->nullable();
        $table->string('billing_city')->nullable();
        $table->string('billing_state')->nullable();
        $table->string('billing_code')->nullable();
        $table->date('bid_due_date')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

it('stores a category when a company is created', function () {
    $request = Request::create('/company_contact', 'POST', [
        'name' => 'Premium Company',
        'category' => CompanyCategoryEnum::PREMIUM->value,
    ]);

    $company = app(CreateCompanyContact::class)->handle($request);

    expect($company->category)->toBe(CompanyCategoryEnum::PREMIUM->value);
});

it('stores no category when none is selected', function () {
    $request = Request::create('/company_contact', 'POST', [
        'name' => 'Uncategorized Company',
        'category' => null,
    ]);

    $company = app(CreateCompanyContact::class)->handle($request);

    expect($company->category)->toBeNull();
});

it('updates a company category', function () {
    $company = CompanyContact::create([
        'name' => 'Advanced Company',
        'category' => CompanyCategoryEnum::ADVANCED->value,
    ]);
    $request = Request::create("/company_contact/{$company->id}", 'PUT', [
        'name' => $company->name,
        'category' => CompanyCategoryEnum::ADVANCED_PLUS->value,
    ]);

    app(UpdateCompanyContact::class)->handle($request, $company);

    expect($company->fresh()->category)->toBe(CompanyCategoryEnum::ADVANCED_PLUS->value);
});

it('resolves the selected order company category for an ESR task', function () {
    $selectedCompany = new CompanyContact(['category' => CompanyCategoryEnum::PREMIUM->value]);
    $selectedContact = new OrderCompanyContact(['is_selected' => true]);
    $selectedContact->setRelation('companyContact', $selectedCompany);

    $order = new Order();
    $order->setRelation('orderCompanyContacts', collect([$selectedContact]));

    $method = new ReflectionMethod(OrderStorageController::class, 'resolveCompanyCategory');
    $category = $method->invoke(new OrderStorageController(), $order);

    expect($category)->toBe(CompanyCategoryEnum::PREMIUM->value);
});

it('uses the client primary company category when an order company is not selected', function () {
    $primaryCompany = new CompanyContact(['category' => CompanyCategoryEnum::ADVANCED->value]);
    $client = new Client();
    $client->setRelation('companyContact', $primaryCompany);

    $order = new Order();
    $order->setRelation('orderCompanyContacts', collect());
    $order->setRelation('client', $client);

    $method = new ReflectionMethod(OrderStorageController::class, 'resolveCompanyCategory');
    $category = $method->invoke(new OrderStorageController(), $order);

    expect($category)->toBe(CompanyCategoryEnum::ADVANCED->value);
});
