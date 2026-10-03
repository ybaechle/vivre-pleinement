<?php

namespace App\Support;

/**
 * Prépare un terme utilisateur pour une clause LIKE : échappe l'antislash et
 * les jokers SQL (% et _) avant de l'entourer des jokers de recherche, pour
 * qu'une recherche sur "50%" ou "a\" garde son sens littéral.
 */
class LikeSearch
{
    public static function wrap(string $term): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
    }
}
