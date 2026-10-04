<?php

use Illuminate\Support\Facades\Schedule;

Schedule::withoutOverlapping()->onOneServer()->environments(['production'])->group(function (): void {
    Schedule::command('appointments:send-reminders')->everyFifteenMinutes();

    /**
     * Filet de sécurité des paiements : rattrape ce qu'un webhook perdu aurait
     * laissé en plan. Sans elle, un client débité par Stripe peut rester sans
     * rendez-vous, sans accès ni livre, et personne n'en est averti.
     */
    Schedule::command('payments:reconcile')->everyFifteenMinutes();

    /**
     * Chaîne de publication des vidéos : la synchro crée les nouvelles vidéos,
     * puis leurs sous-titres sont récupérés. YouTube met parfois plusieurs
     * heures à générer les sous-titres automatiques : une vidéo sans sous-titre
     * est simplement retentée au passage suivant. La fenêtre de 14 jours après
     * l'ajout au site protège le quota de l'API (50 unités par vidéo
     * interrogée) sans écarter une vidéo plus ancienne sur YouTube que la
     * synchro vient de découvrir. La mise en forme IA est ensuite pilotée par
     * n8n via /api/automation/videos.
     */
    Schedule::command('youtube:sync')->hourly();
    Schedule::command('youtube:fetch-transcripts --since=14')->hourlyAt(10);

    /**
     * Un jeton de réinitialisation expiré ne sert plus à rien mais reste
     * associé à l'adresse e-mail de l'élève : on ne le garde pas au-delà de son
     * expiration.
     */
    Schedule::command('auth:clear-resets students')->daily();
    Schedule::command('comments:purge-ips')->daily();
});
