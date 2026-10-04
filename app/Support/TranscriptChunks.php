<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Découpe une transcription brute en morceaux pour l'étape de reponctuation
 * IA, puis recolle les morceaux reponctués en un HTML propre.
 */
class TranscriptChunks
{
    private const ALLOWED_TAGS = '<p><br><em><strong>';

    /**
     * Découpe un texte en morceaux d'environ $chunkWords mots, sans couper un
     * mot.
     *
     * @return list<string>
     */
    public static function split(string $html, int $chunkWords): array
    {
        $groups = array_chunk(self::words($html), max(1, $chunkWords));

        $chunks = [];
        foreach ($groups as $group) {
            $chunks[] = Str::of(implode(' ', $group))->trim()->value();
        }

        return $chunks;
    }

    public static function wordCount(string $html): int
    {
        return count(self::words($html));
    }

    /**
     * Texte lisible d'une transcription HTML, un paragraphe par bloc.
     */
    public static function plainText(string $html): string
    {
        $paragraphs = preg_split('/<\/p>|<br\s*\/?>/i', $html) ?: [];

        return collect($paragraphs)
            ->map(fn (string $paragraph): string => trim(
                html_entity_decode(strip_tags($paragraph)),
            ))
            ->filter()
            ->implode("\n\n");
    }

    /**
     * Recolle les morceaux reponctués en un seul HTML propre.
     */
    public static function assemble(mixed $chunks): string
    {
        if (! is_array($chunks)) {
            return '';
        }

        $clean = [];
        foreach ($chunks as $chunk) {
            $chunk = self::sanitize((string) $chunk);
            if ($chunk !== '') {
                $clean[] = $chunk;
            }
        }

        return Str::of(implode("\n", $clean))->trim()->value();
    }

    /**
     * Réduit un HTML produit par l'IA aux balises de mise en forme de base,
     * sans aucun attribut : il est affiché brut sur le site, un
     * `<p onmouseover>` y serait exécuté.
     */
    public static function sanitize(string $html): string
    {
        return trim((string) preg_replace(
            '/<(\/?)(p|br|em|strong)\b[^>]*>/i',
            '<$1$2>',
            strip_tags($html, self::ALLOWED_TAGS),
        ));
    }

    /**
     * @return list<string>
     */
    private static function words(string $html): array
    {
        $text = trim(html_entity_decode(
            (string) preg_replace('/<[^>]*>/', ' ', $html),
        ));

        return preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
