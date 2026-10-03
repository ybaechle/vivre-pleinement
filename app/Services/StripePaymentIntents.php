<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentIntent;
use Throwable;

/**
 * Cycle de vie d'un PaymentIntent Stripe, partagé par les trois tunnels d'achat
 * (rendez-vous, formations et livre). Ne connaît aucun modèle du domaine : les
 * services appelants gardent la responsabilité de ce qu'un paiement signifie
 * chez eux.
 */
class StripePaymentIntents
{
    /**
     * Statuts pour lesquels un PaymentIntent existant peut resservir au Payment
     * Element au lieu d'en créer un nouveau (risque de double débit sinon).
     *
     * @var list<string>
     */
    private const REUSABLE_STATUSES = [
        'requires_payment_method',
        'requires_confirmation',
        'requires_action',
        'processing',
    ];

    /**
     * Retourne l'intent déjà rattaché à l'achat s'il est encore utilisable, en
     * réalignant son montant si le prix a changé entre-temps. Renvoie null
     * quand il faut en créer un nouveau.
     */
    public function reusable(?string $paymentIntentId, int $amountCents): ?PaymentIntent
    {
        if ($paymentIntentId === null) {
            return null;
        }

        $intent = $this->retrieve($paymentIntentId);

        if ($intent === null || ! in_array($intent->status, self::REUSABLE_STATUSES, true)) {
            return null;
        }

        if ($intent->amount !== $amountCents && str_starts_with($intent->status, 'requires_')) {
            return $this->updateAmount($intent->id, $amountCents);
        }

        return $intent;
    }

    /**
     * Renvoie null uniquement quand Stripe ne connaît pas l'intent : une panne
     * réseau ou une clé invalide doit remonter, sinon l'appelant créerait un
     * nouvel intent ou sauterait un paiement réellement encaissé.
     */
    public function retrieve(string $paymentIntentId): ?PaymentIntent
    {
        try {
            return Cashier::stripe()->paymentIntents->retrieve($paymentIntentId);
        } catch (InvalidRequestException $exception) {
            if ($exception->getStripeCode() === 'resource_missing') {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): PaymentIntent
    {
        return Cashier::stripe()->paymentIntents->create($params);
    }

    public function updateAmount(string $paymentIntentId, int $amountCents): PaymentIntent
    {
        return Cashier::stripe()->paymentIntents->update($paymentIntentId, ['amount' => $amountCents]);
    }

    public function refund(string $paymentIntentId): void
    {
        Cashier::stripe()->refunds->create(['payment_intent' => $paymentIntentId]);
    }

    /**
     * Un paiement réussi arrive pour un achat déjà réglé via un autre intent :
     * le client a payé deux fois (deux onglets avant la réutilisation
     * d'intent, ou course entre webhooks). On rembourse le second débit.
     */
    public function refundDuplicate(Model $purchase, ?string $paymentIntentId, string $label): void
    {
        if ($paymentIntentId === null || $paymentIntentId === $purchase->stripe_payment_intent_id) {
            return;
        }

        $context = [
            'purchase' => $purchase::class.'#'.$purchase->getKey(),
            'kept_payment_intent_id' => $purchase->stripe_payment_intent_id,
            'duplicate_payment_intent_id' => $paymentIntentId,
        ];

        if ($this->refundQuietly($paymentIntentId)) {
            Log::warning("Second paiement détecté pour {$label} : remboursé automatiquement.", $context);

            return;
        }

        Log::error("Second paiement détecté pour {$label} mais remboursement impossible.", $context);
    }

    /**
     * Rembourse sans laisser l'échec interrompre l'appelant : un remboursement
     * raté doit être signalé et traité à la main, pas faire échouer le
     * traitement d'un webhook qui, lui, a bien abouti. Renvoie false si Stripe
     * a refusé.
     */
    public function refundQuietly(string $paymentIntentId): bool
    {
        try {
            $this->refund($paymentIntentId);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            Log::error('Remboursement Stripe impossible : à traiter dans le dashboard.', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return false;
        }
    }
}
