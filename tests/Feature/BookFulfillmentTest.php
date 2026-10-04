<?php

use App\Enums\BookOrderStatus;
use App\Mail\BookOrderConfirmation;
use App\Mail\BookOrderNotification;
use App\Models\BookOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Events\WebhookReceived;

function bookPaymentWebhook(BookOrder $order, string $intentId = 'pi_book_test', int $amount = 3700): void
{
    event(new WebhookReceived([
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => [
            'id' => $intentId,
            'amount_received' => $amount,
            'currency' => 'eur',
            'metadata' => ['book_order_id' => (string) $order->id],
        ]],
    ]));
}

it('marque la commande payée sur payment_intent.succeeded', function () {
    Mail::fake();
    $order = BookOrder::factory()->create();

    bookPaymentWebhook($order);

    $order->refresh();

    expect($order->status)->toBe(BookOrderStatus::Paid)
        ->and($order->stripe_payment_intent_id)->toBe('pi_book_test')
        ->and($order->paid_at)->not->toBeNull();
});

it('envoie le lien au client et la notification à l\'admin', function () {
    Mail::fake();
    $order = BookOrder::factory()->create();

    bookPaymentWebhook($order);

    Mail::assertQueued(BookOrderConfirmation::class);
    Mail::assertQueued(BookOrderNotification::class);
});

it('enregistre le montant réellement encaissé, pas le prix courant', function () {
    Mail::fake();
    $order = BookOrder::factory()->create(['amount_cents' => 3700]);

    bookPaymentWebhook($order, amount: 2900);

    expect($order->fresh()->amount_cents)->toBe(2900);
});

it('ignore un webhook dupliqué sans renvoyer les emails', function () {
    Mail::fake();
    $order = BookOrder::factory()->create();

    bookPaymentWebhook($order);
    bookPaymentWebhook($order);

    Mail::assertQueuedCount(2);
});

it('échoue bruyamment sur un paiement dont la commande est introuvable', function () {
    Mail::fake();
    $order = BookOrder::factory()->create();

    expect(fn () => event(new WebhookReceived([
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => [
            'id' => 'pi_autre',
            'metadata' => ['book_order_id' => '999999'],
        ]],
    ])))->toThrow(ModelNotFoundException::class);

    expect($order->fresh()->status)->toBe(BookOrderStatus::Pending);
});

it('révoque le téléchargement sur charge.refunded', function () {
    $order = BookOrder::factory()->paid()->create(['stripe_payment_intent_id' => 'pi_book_test']);

    chargeRefundedWebhook('pi_book_test');

    $order->refresh();

    expect($order->status)->toBe(BookOrderStatus::Refunded)
        ->and($order->refunded_at)->not->toBeNull();
});

it('ne touche pas à une commande jamais payée lors d\'un remboursement', function () {
    $order = BookOrder::factory()->create(['stripe_payment_intent_id' => 'pi_book_test']);

    chargeRefundedWebhook('pi_book_test');

    expect($order->fresh()->status)->toBe(BookOrderStatus::Pending);
});

it('ignore un remboursement dont le PaymentIntent est inconnu', function () {
    $order = BookOrder::factory()->paid()->create(['stripe_payment_intent_id' => 'pi_book_test']);

    chargeRefundedWebhook('pi_inconnu');

    expect($order->fresh()->status)->toBe(BookOrderStatus::Paid);
});
