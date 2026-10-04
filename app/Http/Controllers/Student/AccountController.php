<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteStudentAccountFormRequest;
use App\Http\Requests\UpdateStudentPasswordFormRequest;
use App\Http\Requests\UpdateStudentProfileFormRequest;
use App\Models\Student;
use App\Support\StudentAnonymizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('student.account.edit', [
            'student' => $request->user('student'),
        ]);
    }

    /**
     * Met à jour le nom et l'e-mail. Un changement d'e-mail repasse le compte
     * en « non vérifié » et renvoie un lien de confirmation.
     */
    public function updateProfile(UpdateStudentProfileFormRequest $request): RedirectResponse
    {
        /** @var Student $student */
        $student = $request->user('student');

        $validated = $request->validated();

        $emailChanged = $validated['email'] !== $student->email;

        $student->fill(Arr::only($validated, ['name', 'email']));

        if ($emailChanged) {
            $student->email_verified_at = null;
        }

        $student->save();

        if ($emailChanged) {
            $student->sendEmailVerificationNotification();

            return back()->with('status', 'profile-updated-email-verification');
        }

        return back()->with('status', 'profile-updated');
    }

    /**
     * Change le mot de passe après vérification du mot de passe actuel.
     * Le hachage est assuré par le cast `hashed` du modèle Student.
     */
    public function updatePassword(UpdateStudentPasswordFormRequest $request): RedirectResponse
    {
        /** @var Student $student */
        $student = $request->user('student');

        $student->update([
            'password' => $request->validated('password'),
        ]);

        return back()->with('status', 'password-updated');
    }

    /**
     * Anonymise le compte élève (droit à l'effacement RGPD) tout en conservant
     * les inscriptions pour les obligations comptables. Voir StudentAnonymizer.
     * Le mot de passe courant est revérifié par
     * DeleteStudentAccountFormRequest.
     */
    public function destroy(DeleteStudentAccountFormRequest $request): RedirectResponse
    {
        $student = $request->user('student');

        StudentAnonymizer::anonymize($student);

        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('courses.index')
            ->with('status', 'Votre compte a été supprimé. À bientôt.');
    }
}
