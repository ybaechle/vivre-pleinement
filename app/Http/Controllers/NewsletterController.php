<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterFormRequest;
use App\Jobs\SubscribeToNewsletterJob;
use App\Support\SubmissionThrottle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class NewsletterController extends Controller
{
    public function store(NewsletterFormRequest $request): RedirectResponse|JsonResponse
    {
        $retryAfter = SubmissionThrottle::attempt('newsletter:'.$request->ip());

        if ($retryAfter !== null) {
            return $this->failure($request, "Trop d'envois. Réessayez dans {$retryAfter}s.");
        }

        $data = $request->validated();

        /**
         * L'appel à Brevo part en file : un échec y est retenté puis journalisé
         * par le job, il ne peut pas être signalé au visiteur ici.
         */
        SubscribeToNewsletterJob::dispatch($data['email'], $data['first_name'], route('newsletter.confirmed'));

        if ($request->wantsJson()) {
            return response()->json(['status' => 'pending']);
        }

        return redirect()->to(route('home').'#capture')->with('newsletter_status', 'pending');
    }

    /**
     * Réponse d'échec : JSON pour les requêtes AJAX, redirection sinon.
     */
    private function failure(NewsletterFormRequest $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['errors' => ['email' => [$message]]], 422);
        }

        return redirect()
            ->to(route('home').'#capture')
            ->withInput($request->only(['first_name', 'email']))
            ->withErrors(['email' => $message]);
    }
}
