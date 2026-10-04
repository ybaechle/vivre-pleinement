<?php

/**
 * Conventions du projet vérifiées sur le texte des sources, hors suite de
 * tests (qui ne teste que des comportements). Elles se dégradent seules dès
 * qu'on ajoute une section sans y penser : ce script fait échouer la CI
 * plutôt que de laisser la dérive se découvrir des mois plus tard.
 *
 * Usage : composer lint:conventions
 */
$root = dirname(__DIR__);

/**
 * @return array<string, string> contenu indexé par chemin relatif
 */
function sources(string $root, string $directory, string $suffix): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$directory}", FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (str_ends_with($file->getFilename(), $suffix)) {
            $files[substr($file->getPathname(), strlen($root) + 1)] = file_get_contents($file->getPathname());
        }
    }

    ksort($files);

    return $files;
}

$views = sources($root, 'resources/views', '.blade.php');
$php = sources($root, 'app', '.php');
$filament = array_filter($php, fn (string $path): bool => str_starts_with($path, 'app/Filament/'), ARRAY_FILTER_USE_KEY);

$matching = fn (array $files, callable $test): array => array_keys(array_filter($files, $test));

/**
 * Valeurs d'une classe ou d'un attribut relevées dans les fichiers, hors
 * échelle autorisée.
 *
 * @param  list<string>  $allowed
 * @return list<string>
 */
$outsideScale = function (array $files, string $pattern, array $allowed): array {
    $found = [];

    foreach ($files as $content) {
        preg_match_all($pattern, $content, $matches);
        $found = array_merge($found, $matches[1]);
    }

    return array_values(array_unique(array_diff($found, $allowed)));
};

$eyebrowsOverCap = [];
foreach (['home' => 8, 'book' => 11, 'therapie-act' => 6] as $page => $sections) {
    $eyebrows = array_sum(array_map(
        fn (string $content): int => substr_count($content, 'eyebrow="'),
        array_filter($views, fn (string $path): bool => str_starts_with($path, "resources/views/{$page}/"), ARRAY_FILTER_USE_KEY),
    ));

    if ($eyebrows > (int) ceil($sections / 3)) {
        $eyebrowsOverCap[] = "{$page} ({$eyebrows} eyebrows pour {$sections} sections)";
    }
}

$checks = [
    'Tiret cadratin (—) dans une vue' => $matching($views, fn (string $c): bool => str_contains($c, '—')),

    'h-screen au lieu de dvh' => $matching($views, fn (string $c): bool => preg_match('/\b(min-)?h-screen\b/', $c) === 1),

    /**
     * Les surcharges d'un seul angle (rounded-br-md) échappent à l'échelle :
     * elles dessinent la pointe d'une bulle de dialogue dans l'accordéon.
     */
    'Rayon hors échelle (full, 5xl, 4xl, 3xl, 2xl, sm)' => $outsideScale(
        $views,
        '/\brounded-(?!t-|b-|l-|r-|tl-|tr-|bl-|br-)([a-z0-9]+)\b/',
        ['full', '5xl', '4xl', '3xl', '2xl', 'sm'],
    ),

    'Épaisseur de trait SVG hors échelle (1.8, 2.5, 4, 1)' => $outsideScale(
        $views,
        '/stroke-width="([0-9.]+)"/',
        ['1.8', '2.5', '4', '1'],
    ),

    'Libellé « Prendre RDV » au lieu de « Prendre rendez-vous »' => $matching($views, fn (string $c): bool => str_contains($c, 'Prendre RDV')),

    'Pastille décorative dans une pilule' => $matching(
        $views,
        fn (string $c): bool => preg_match('/<span class="[^"]*size-1\.5 rounded-full[^"]*"><\/span>/', $c) === 1,
    ),

    'Plus d\'un eyebrow pour trois sections' => $eyebrowsOverCap,

    /**
     * Blade analyse le gabarit avec token_get_all() : avec short_open_tag
     * actif, comme en production, un "<?" littéral ouvre un bloc PHP et la vue
     * part en erreur 500. Le réglage n'étant pas modifiable à l'exécution,
     * seule la source peut être vérifiée.
     */
    'Balise PHP ouvrante littérale dans une vue' => $matching($views, fn (string $c): bool => str_contains($c, '<'.'?')),

    /**
     * Laravel ne comprend que les intervalles {1} ou [2,*] : la notation ICU
     * ]1,*[ n'est ni choisie ni nettoyée et s'afficherait telle quelle.
     */
    'Intervalle de pluriel que Laravel ne sait pas lire' => $matching(
        $php,
        fn (string $c): bool => str_contains($c, ']1,*[') || preg_match('/\]\d+,/', $c) === 1,
    ),

    'Table admin vide sans icône' => $matching(
        $filament,
        fn (string $c): bool => str_contains($c, '->emptyStateHeading(') && ! str_contains($c, '->emptyStateIcon('),
    ),

    'Emoji dans l\'interface admin' => $matching(
        $filament,
        fn (string $c): bool => preg_match('/[\x{26A0}\x{2709}\x{2713}\x{1F300}-\x{1FAFF}]/u', $c) === 1,
    ),
];

$failures = array_filter($checks);

foreach ($failures as $rule => $offenders) {
    fwrite(STDERR, "✗ {$rule}\n");
    foreach ($offenders as $offender) {
        fwrite(STDERR, "    {$offender}\n");
    }
}

if ($failures !== []) {
    exit(1);
}

echo 'Conventions respectées ('.count($checks)." règles).\n";
