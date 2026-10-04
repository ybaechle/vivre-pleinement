<?php

use App\Models\Availability;
use App\Support\Weekdays;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Cashier\Events\WebhookReceived;
use Stripe\PaymentIntent;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(LazilyRefreshDatabase::class)->in('Feature');

/**
 * Horodatage de formulaire chiffré, antidaté pour franchir le délai minimum
 * exigé par ChecksSubmissionDelay. `$secondsAgo = 0` simule une soumission
 * instantanée, donc robotique.
 */
function submissionStamp(int $secondsAgo = 5): string
{
    return Crypt::encryptString((string) (time() - $secondsAgo));
}

/**
 * Construit un état de formulaire complet : tous les jours fermés, sauf ceux
 * décrits par $openDays (clé = dayOfWeek Carbon, valeur = liste de plages).
 *
 * @param  array<int, array<int, array{start_time: string, end_time: string}>>  $openDays
 * @return array<string, mixed>
 */
function scheduleFormState(array $openDays, ?int $serviceId = null): array
{
    $days = [];

    foreach (Weekdays::orderedKeys() as $day) {
        $days["day_{$day}"] = [
            'is_open' => isset($openDays[$day]),
            'ranges' => $openDays[$day] ?? [],
        ];
    }

    return [
        'appointment_service_id' => $serviceId,
        'days' => $days,
    ];
}

/**
 * Simule le webhook Stripe `charge.refunded`. `$fullyRefunded = false`
 * représente un remboursement partiel, que Stripe signale aussi par cet événement.
 */
function chargeRefundedWebhook(?string $paymentIntentId, bool $fullyRefunded = true): void
{
    event(new WebhookReceived([
        'type' => 'charge.refunded',
        'data' => ['object' => [
            'id' => 'ch_test_refund',
            'payment_intent' => $paymentIntentId,
            'refunded' => $fullyRefunded,
        ]],
    ]));
}

/**
 * Ouvre l'agenda tous les jours de la semaine, avec les horaires par défaut
 * de la factory sauf précision.
 *
 * @param  array<string, mixed>  $attributes
 */
function openEveryDay(array $attributes = []): void
{
    foreach (range(0, 6) as $dayOfWeek) {
        Availability::factory()->dayOfWeek($dayOfWeek)->create($attributes);
    }
}

/**
 * PaymentIntent tel que le renverrait l'API Stripe.
 */
function stripeIntent(string $id, string $status, int $amount): PaymentIntent
{
    return PaymentIntent::constructFrom([
        'id' => $id,
        'status' => $status,
        'amount' => $amount,
        'client_secret' => $id.'_secret',
    ]);
}
