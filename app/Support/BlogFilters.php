<?php

namespace App\Support;

class BlogFilters
{
    /**
     * Construit la query string en gardant les filtres actuels, et en
     * remplaçant/retirant ceux passés.
     *
     * @param  array<string, mixed>  $current  Filtres actifs, depuis la
     *                                         request.
     * @param  array<string, mixed>  $merge  Clés à modifier (null pour retirer)
     */
    public static function url(string $route, array $current, array $merge = []): string
    {
        $params = array_filter(
            array_merge($current, $merge),
            fn ($value) => $value !== null && $value !== '' && $value !== false,
        );

        return route($route, $params);
    }
}
