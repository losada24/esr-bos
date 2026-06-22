<?php

use App\Http\Requests\StoreQualifiedOrderRequest;
use App\Http\Requests\UpdateQualifiedOrderRequest;

it('validates four additional commercial company associations when creating an order', function () {
    $rules = (new StoreQualifiedOrderRequest())->rules();

    foreach (range(1, 4) as $index) {
        expect($rules)->toHaveKeys([
            "associate_company_contact_id_{$index}",
            "associate_client_id_{$index}",
            "associate_source_id_{$index}",
        ]);
    }
});

it('validates four additional commercial company associations when updating an order', function () {
    $rules = (new UpdateQualifiedOrderRequest())->rules();

    foreach (range(1, 4) as $index) {
        expect($rules)->toHaveKeys([
            "associate_company_contact_id_{$index}",
            "associate_client_id_{$index}",
            "associate_source_id_{$index}",
        ]);
    }
});
