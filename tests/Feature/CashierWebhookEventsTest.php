<?php

use Stripe\StripeClient;

it('subscribes the Stripe webhook to the payment events handled by the application', function () {
    $endpoints = Mockery::mock();
    $endpoints->shouldReceive('create')->once()->withArgs(
        fn (array $params) => in_array('payment_intent.succeeded', $params['enabled_events'], true)
            && in_array('charge.refunded', $params['enabled_events'], true),
    )->andReturn((object) ['id' => 'we_test']);

    $this->app->bind(StripeClient::class, fn () => (object) ['webhookEndpoints' => $endpoints]);

    $this->artisan('cashier:webhook', ['--url' => 'https://example.com/stripe/webhook'])->assertSuccessful();
});
