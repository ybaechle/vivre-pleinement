<?php

namespace App\Http\Controllers\Student\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->intended(route('student.dashboard'))
            ->with('status', "Votre adresse e-mail a bien été confirmée. Bienvenue\u{00A0}!");
    }
}
