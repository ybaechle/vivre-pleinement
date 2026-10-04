<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Student;
use App\Support\StudentAnonymizer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('anonymise le compte élève en conservant la vente', function () {
    $student = Student::factory()->create(['name' => 'Camille Dupont', 'email' => 'camille@example.com']);
    $course = Course::factory()->create();
    $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

    StudentAnonymizer::anonymize($student);

    $fresh = $student->fresh();
    expect($fresh->name)->toBe('Compte supprimé')
        ->and($fresh->email)->not->toBe('camille@example.com')
        ->and($fresh->isAnonymized())->toBeTrue();

    $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'student_id' => $student->id]);
});

it('efface la progression lors de l\'anonymisation', function () {
    $student = Student::factory()->create();
    $course = Course::factory()->create();
    $module = Module::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id]);
    $student->lessonProgress()->create(['lesson_id' => $lesson->id, 'completed_at' => now()]);

    StudentAnonymizer::anonymize($student);

    $this->assertDatabaseMissing('lesson_progress', ['student_id' => $student->id]);
});

it('efface les données Stripe et le lien de réinitialisation lors de l\'anonymisation', function () {
    $student = Student::factory()->create(['stripe_id' => 'cus_test', 'pm_type' => 'card', 'pm_last_four' => '4242']);
    Password::broker('students')->createToken($student);

    StudentAnonymizer::anonymize($student);

    expect($student->fresh()->only(['stripe_id', 'pm_type', 'pm_last_four']))
        ->toBe(['stripe_id' => null, 'pm_type' => null, 'pm_last_four' => null]);
    $this->assertDatabaseCount('student_password_reset_tokens', 0);
});

it('supprime le compte depuis le tableau de bord élève', function () {
    $student = Student::factory()->create(['password' => Hash::make('motdepasse-actuel')]);

    $this->actingAs($student, 'student')
        ->delete(route('student.account.destroy'), ['current_password' => 'motdepasse-actuel'])
        ->assertRedirect(route('courses.index'));

    expect($student->fresh()->isAnonymized())->toBeTrue();
    expect(auth('student')->check())->toBeFalse();
});

it('refuse la suppression du compte sans le mot de passe actuel', function () {
    $student = Student::factory()->create(['password' => Hash::make('motdepasse-actuel')]);

    $this->actingAs($student, 'student')
        ->delete(route('student.account.destroy'))
        ->assertSessionHasErrors('current_password', errorBag: 'deleteAccount');

    expect($student->fresh()->isAnonymized())->toBeFalse();
    expect(auth('student')->check())->toBeTrue();
});

it('refuse la suppression du compte avec un mot de passe faux', function () {
    $student = Student::factory()->create(['password' => Hash::make('motdepasse-actuel')]);

    $this->actingAs($student, 'student')
        ->delete(route('student.account.destroy'), ['current_password' => 'mauvais'])
        ->assertSessionHasErrors('current_password', errorBag: 'deleteAccount');

    expect($student->fresh()->isAnonymized())->toBeFalse();
});

it('confirme la suppression du compte sur la page des formations', function () {
    $student = Student::factory()->create(['password' => Hash::make('motdepasse-actuel')]);

    $this->actingAs($student, 'student')
        ->followingRedirects()
        ->delete(route('student.account.destroy'), ['current_password' => 'motdepasse-actuel'])
        ->assertOk()
        ->assertSee('Votre compte a été supprimé.');
});
