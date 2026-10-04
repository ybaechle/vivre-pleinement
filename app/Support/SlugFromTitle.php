<?php

namespace App\Support;

use Closure;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SlugFromTitle
{
    /**
     * Callback `afterStateUpdated` d'un champ titre de l'admin : recopie le
     * titre dans le slug à la création seulement. Un slug déjà publié ne doit
     * plus changer, ses liens entrants casseraient.
     */
    public static function onCreate(): Closure
    {
        return function (?string $state, Set $set, ?Model $record): void {
            if ($record === null) {
                $set('slug', Str::slug((string) $state));
            }
        };
    }
}
