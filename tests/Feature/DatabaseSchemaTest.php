<?php

use Illuminate\Support\Facades\Schema;

it('indexes the payment intent column used to find appointments from Stripe', function () {
    $indexedColumns = collect(Schema::getIndexes('appointments'))->pluck('columns')->flatten();

    expect($indexedColumns)->toContain('stripe_payment_intent_id');
});

it('no longer carries the unused subscription tables and legacy columns', function () {
    expect(Schema::hasTable('subscriptions'))->toBeFalse()
        ->and(Schema::hasTable('subscription_items'))->toBeFalse()
        ->and(Schema::hasColumns('posts', ['seo_canonical']))->toBeFalse()
        ->and(Schema::hasColumns('posts', ['seo_schema_json']))->toBeFalse()
        ->and(Schema::hasColumns('products', ['stripe_payment_link']))->toBeFalse()
        ->and(Schema::hasColumns('users', ['stripe_id']))->toBeFalse();
});
