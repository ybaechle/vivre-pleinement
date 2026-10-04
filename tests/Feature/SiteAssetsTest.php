<?php

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Disque simulé : sans cela, chaque exécution laisse un faux PDF dans
 * storage/app/private, que le rollback de la base ne nettoie pas.
 */
beforeEach(fn () => Storage::fake('local'));

it('affiche le prix du livre depuis le catalogue sur l\'accueil', function () {
    $product = Product::factory()->create([
        'slug' => 'livre',
        'name' => 'Le livre seul',
        'price_cents' => 4200,
    ]);

    $product->addMedia(UploadedFile::fake()->create('livre.pdf', 12))
        ->toMediaCollection('download');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Découvrir le livre · 42', false);
});

it('masque le prix du livre sur l\'accueil quand l\'offre n\'est pas livrable', function () {
    Product::factory()->create(['slug' => 'livre', 'price_cents' => 4200]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Découvrir le livre')
        ->assertDontSee('Découvrir le livre · ', false);
});

it('sert des icônes de site non vides', function (string $path) {
    /**
     * Le favicon.ico du dépôt a longtemps pesé zéro octet : l'existence du
     * fichier ne suffit pas, il faut vérifier qu'il a du contenu.
     */
    $file = public_path($path);

    expect(file_exists($file))->toBeTrue("$path est absent")
        ->and(filesize($file))->toBeGreaterThan(500, "$path est vide ou tronqué");
})->with([
    'favicon.ico',
    'apple-touch-icon.png',
    'icon-192.png',
    'icon-512.png',
    'site.webmanifest',
]);

it('déclare les icônes et le manifeste dans le layout', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('rel="icon"', false)
        ->assertSee('rel="apple-touch-icon"', false)
        ->assertSee('rel="manifest"', false)
        ->assertSee('name="theme-color"', false);
});
