<?php

use App\Listeners\HandleStripeChargeRefunded;
use App\Listeners\HandleStripePaymentSucceeded;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

dataset('stripe webhook listeners', [
    'payment_intent.succeeded' => [HandleStripePaymentSucceeded::class, 'payment_intent.succeeded'],
    'charge.refunded' => [HandleStripeChargeRefunded::class, 'charge.refunded'],
]);

it('retries processing several times with a backoff before giving up', function (string $listenerClass) {
    $listener = app($listenerClass);

    expect($listener->tries)->toBeGreaterThan(1)
        ->and($listener->backoff)->toBeArray()
        ->and($listener->backoff)->not->toBeEmpty();
})->with('stripe webhook listeners');

it('logs critically without personal data when processing is exhausted', function (string $listenerClass, string $type) {
    Log::spy();

    $event = new WebhookReceived([
        'id' => 'evt_test',
        'type' => $type,
        'data' => ['object' => [
            'id' => 'pi_test',
            'payment_intent' => 'pi_test',
            'receipt_email' => 'camille@example.com',
            'billing_details' => ['name' => 'Camille Martin', 'email' => 'camille@example.com'],
        ]],
    ]);

    app($listenerClass)->failed($event, new RuntimeException('Stripe indisponible'));

    Log::shouldHaveReceived('critical')->once()->withArgs(
        fn (string $message, array $context) => $context['event_id'] === 'evt_test'
            && $context['payment_intent'] === 'pi_test'
            && ! str_contains(json_encode($context), 'camille@example.com'),
    );
})->with('stripe webhook listeners');
