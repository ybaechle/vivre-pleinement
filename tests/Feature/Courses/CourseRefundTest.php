<?php

use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\Enrollments\Pages\ListEnrollments;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

function activeEnrollment(string $intentId = 'pi_refund_test'): Enrollment
{
    return Enrollment::factory()->create([
        'student_id' => Student::factory()->create()->id,
        'course_id' => Course::factory()->create()->id,
        'stripe_payment_intent_id' => $intentId,
    ]);
}

it('révoque l\'accès sur un webhook charge.refunded', function () {
    $enrollment = activeEnrollment();

    chargeRefundedWebhook('pi_refund_test');

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Refunded);
});

it('retire l\'accès aux leçons à un élève remboursé', function () {
    $enrollment = activeEnrollment();

    chargeRefundedWebhook('pi_refund_test');

    expect($enrollment->student->fresh()->hasAccessTo($enrollment->course))->toBeFalse();
});

it('conserve l\'accès sur un remboursement partiel', function () {
    $enrollment = activeEnrollment();

    chargeRefundedWebhook('pi_refund_test', fullyRefunded: false);

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Active);
});

it('ignore un remboursement dont le PaymentIntent est inconnu', function () {
    $enrollment = activeEnrollment();

    chargeRefundedWebhook('pi_autre_paiement');

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Active);
});

it('ignore un webhook charge.refunded sans payment_intent', function () {
    $enrollment = activeEnrollment();

    chargeRefundedWebhook(null);

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Active);
});

it('ne réactive pas une inscription en attente lors d\'un remboursement', function () {
    $enrollment = Enrollment::factory()->pending()->create([
        'stripe_payment_intent_id' => 'pi_refund_test',
    ]);

    chargeRefundedWebhook('pi_refund_test');

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Pending);
});

it('permet à un admin de marquer une inscription comme remboursée', function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $enrollment = activeEnrollment();

    Livewire::test(ListEnrollments::class)
        ->callAction(TestAction::make('markRefunded')->table($enrollment))
        ->assertHasNoActionErrors();

    expect($enrollment->fresh()->status)->toBe(EnrollmentStatus::Refunded);
});
