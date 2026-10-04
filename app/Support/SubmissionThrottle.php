<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Limitation d'envois pour les formulaires publics (contact, commentaires).
 */
class SubmissionThrottle
{
    private const MAX_ATTEMPTS = 3;

    private const DECAY_SECONDS = 600;

    /**
     * Consomme une tentative si le plafond n'est pas atteint. Renvoie `null`
     * quand l'envoi est autorisé, sinon le nombre de secondes à attendre.
     */
    public static function attempt(string $key): ?int
    {
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return RateLimiter::availableIn($key);
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        return null;
    }
}
