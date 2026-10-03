<?php

use Illuminate\Support\Facades\Schema;

it('indexes the payment intent column used to find appointments from Stripe', function () {
    $indexedColumns = collect(Schema::getIndexes('appointments'))->pluck('columns')->flatten();

    expect($indexedColumns)->toContain('stripe_payment_intent_id');
});
