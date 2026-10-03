<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Mail\CourseAccessGranted;
use App\Mail\CoursePurchaseNotification;
use App\Models\Enrollment;
use App\Models\Student;
use App\Support\SiteContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\PaymentIntent;

class CoursePaymentService
{
    public function __construct(
        private StripePaymentIntents $intents,
    ) {}

    /**
     * Retourne le PaymentIntent de l'inscription : réutilise celui déjà créé
     * (rafraîchissement, second onglet) tant qu'il n'est pas finalisé, sinon en
     * crée un nouveau. Le client_secret retourné alimente le Payment Element.
     * L'inscription est activée plus tard par le webhook
     * payment_intent.succeeded.
     */
    public function getOrCreatePaymentIntent(Enrollment $enrollment): PaymentIntent
    {
        $enrollment->loadMissing(['course', 'student']);

        $existing = $this->intents->reusable(
            $enrollment->stripe_payment_intent_id,
            $enrollment->course->price_cents,
        );

        if ($existing !== null) {
            return $existing;
        }

        $intent = $this->intents->create([
            'amount' => $enrollment->course->price_cents,
            'currency' => strtolower($enrollment->course->currency ?? config('cashier.currency', 'eur')),
            'customer' => $this->resolveStripeCustomerId($enrollment->student),
            'description' => 'Formation : '.$enrollment->course->title,
            'receipt_email' => $enrollment->student->email,
            'metadata' => [
                'enrollment_id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'student_id' => $enrollment->student_id,
            ],
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        $enrollment->update(['stripe_payment_intent_id' => $intent->id]);

        return $intent;
    }

    public function resolveStripeCustomerId(Student $student): string
    {
        return $student->createOrGetStripeCustomer()->id;
    }

    /**
     * Active l'inscription après paiement réussi, puis notifie l'élève et
     * l'admin. Idempotent : un webhook dupliqué pour une inscription déjà
     * active ne fait rien ; un paiement concurrent (intent différent) est
     * remboursé d'office. Verrouille la ligne le temps de la vérification pour
     * empêcher deux workers de traiter le même webhook en double (deux mails de
     * bienvenue). Le montant et la devise enregistrés viennent du payload
     * Stripe : c'est ce qui a réellement été payé, pas le prix courant du
     * cours.
     *
     * Seules la vérification et la mise à jour du statut sont verrouillées :
     * l'appel réseau Stripe (remboursement) et l'envoi des mails se font après
     * le COMMIT, pour ne jamais faire lire à un job de mail en file un
     * enregistrement pas encore validé.
     */
    public function fulfill(
        Enrollment $enrollment,
        ?string $paymentIntentId = null,
        ?int $amountReceivedCents = null,
        ?string $currency = null,
    ): void {
        [$outcome, $locked] = DB::transaction(function () use ($enrollment, $paymentIntentId, $amountReceivedCents, $currency) {
            /** @var Enrollment $locked */
            $locked = Enrollment::query()->whereKey($enrollment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === EnrollmentStatus::Active) {
                return ['duplicate', $locked];
            }

            $locked->loadMissing(['student', 'course']);

            $locked->update([
                'status' => EnrollmentStatus::Active,
                'amount_paid_cents' => $amountReceivedCents ?? $locked->course->price_cents,
                'currency' => $currency !== null ? strtoupper($currency) : $locked->course->currency,
                'stripe_payment_intent_id' => $paymentIntentId,
                'purchased_at' => now(),
            ]);

            return ['fulfilled', $locked];
        });

        match ($outcome) {
            'duplicate' => $this->intents->refundDuplicate($locked, $paymentIntentId, 'une inscription déjà active'),
            'fulfilled' => $this->sendAccessGranted($locked),
        };
    }

    private function sendAccessGranted(Enrollment $enrollment): void
    {
        Mail::to($enrollment->student->email)->send(new CourseAccessGranted($enrollment));
        Mail::to(SiteContact::notifyEmail())
            ->send(new CoursePurchaseNotification($enrollment));
    }

    /**
     * Révoque l'accès après un remboursement (webhook charge.refunded ou action
     * admin). Idempotent : une inscription déjà remboursée ou en attente ne
     * bouge pas.
     */
    public function refund(Enrollment $enrollment): void
    {
        if ($enrollment->status !== EnrollmentStatus::Active) {
            return;
        }

        $enrollment->update(['status' => EnrollmentStatus::Refunded]);

        Log::info('Inscription remboursée, accès révoqué.', [
            'enrollment_id' => $enrollment->id,
            'course_id' => $enrollment->course_id,
            'student_id' => $enrollment->student_id,
        ]);
    }
}
