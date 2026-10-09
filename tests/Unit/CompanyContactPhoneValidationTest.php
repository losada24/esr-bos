<?php

use App\Http\Requests\StoreCompanyContactRequest;
use App\Http\Requests\UpdateCompanyContactRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');

    Schema::create('clients', function (Blueprint $table) {
        $table->id();
        $table->string('phone');
        $table->softDeletes();
    });
});

it('allows an existing client phone when the matching duplicate is soft deleted during a company update', function () {
    DB::table('clients')->insert([
        ['id' => 1, 'phone' => '555-0100', 'deleted_at' => null],
        ['id' => 2, 'phone' => '555-0100', 'deleted_at' => now()],
    ]);

    $request = UpdateCompanyContactRequest::create('/company_contact/156', 'PUT', [
        'name' => 'Premium Company',
        'category' => 'PREMIUM',
        'clients' => [
            ['id' => 1, 'phone' => '555-0100'],
        ],
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->passes())->toBeTrue();
});

it('allows a new client phone when the only matching client is soft deleted', function () {
    DB::table('clients')->insert([
        'phone' => '555-0200',
        'deleted_at' => now(),
    ]);

    $request = StoreCompanyContactRequest::create('/company_contact', 'POST', [
        'name' => 'Premium Company',
        'category' => 'PREMIUM',
        'clients' => [
            ['phone' => '555-0200'],
        ],
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->passes())->toBeTrue();
});

