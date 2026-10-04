<?php

use App\Models\Student;
use App\Notifications\StudentVerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('met en file d\'attente la notification de vérification d\'e-mail', function () {
    expect(new StudentVerifyEmail)->toBeInstanceOf(ShouldQueue::class);
});

it('redirige un élève non vérifié vers la page de confirmation depuis le tableau de bord', function () {
    $student = Student::factory()->unverified()->create();

    $this->actingAs($student, 'student')
        ->get(route('student.dashboard'))
        ->assertRedirect(route('student.verification.notice'));
});

it('laisse un élève vérifié accéder au tableau de bord', function () {
    $student = Student::factory()->create();

    $this->actingAs($student, 'student')
        ->get(route('student.dashboard'))
        ->assertOk();
});

it('vérifie l\'e-mail via le lien signé', function () {
    $student = Student::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'student.verification.verify',
        now()->addMinutes(60),
        ['id' => $student->id, 'hash' => sha1($student->getEmailForVerification())],
    );

    Event::fake();

    $this->actingAs($student, 'student')
        ->get($url)
        ->assertRedirect(route('student.dashboard'));

    expect($student->refresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);

    $this->get(route('student.dashboard'))->assertSee('Votre adresse e-mail a bien été confirmée.');
});

it('refuse de vérifier l\'adresse d\'un autre élève que celui connecté', function () {
    $other = Student::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'student.verification.verify',
        now()->addMinutes(60),
        ['id' => $other->id, 'hash' => sha1($other->getEmailForVerification())],
    );

    $this->actingAs(Student::factory()->unverified()->create(), 'student')
        ->get($url)
        ->assertForbidden();

    expect($other->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejette un lien de vérification au hash invalide', function () {
    $student = Student::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'student.verification.verify',
        now()->addMinutes(60),
        ['id' => $student->id, 'hash' => sha1('mauvais@example.com')],
    );

    $this->actingAs($student, 'student')
        ->get($url)
        ->assertForbidden();

    expect($student->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('renvoie un lien de vérification à la demande', function () {
    Notification::fake();

    $student = Student::factory()->unverified()->create();

    $this->actingAs($student, 'student')
        ->post(route('student.verification.send'))
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($student, StudentVerifyEmail::class);
});

it('envoie la notification de vérification à l\'inscription', function () {
    Notification::fake();

    $this->post(route('student.register.store'), [
        'name' => 'Nouvelle Élève',
        'email' => 'nouvelle@example.com',
        'password' => 'motdepasse-solide',
        'password_confirmation' => 'motdepasse-solide',
    ]);

    $student = Student::where('email', 'nouvelle@example.com')->firstOrFail();
    Notification::assertSentTo($student, StudentVerifyEmail::class);
});
