<?php

namespace App\Support;

class Duration
{
    /**
     * Format horloge sans zéro initial : « 4:05 », ou « 1:02:03 » au-delà
     * d'une heure.
     */
    public static function clock(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds % 60)
            : sprintf('%d:%02d', $minutes, $seconds % 60);
    }
}
