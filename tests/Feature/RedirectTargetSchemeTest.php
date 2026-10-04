<?php

use App\Filament\Admin\Resources\Redirects\Pages\CreateRedirect;
use App\Models\Redirect;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('rewrites a relative target onto the site domain', function () {
    Redirect::factory()->create([
        'from_path' => '/ancienne-page',
        'to_path' => '/blog',
        'status_code' => 301,
    ]);

    $this->get('/ancienne-page')->assertRedirect(url('/blog'));
});

it('follows an absolute https target', function () {
    Redirect::factory()->create([
        'from_path' => '/partenaire',
        'to_path' => 'https://example.com/page',
        'status_code' => 301,
    ]);

    $this->get('/partenaire')->assertRedirect('https://example.com/page');
});

it('keeps a protocol relative target on the site domain', function () {
    Redirect::factory()->create([
        'from_path' => '/relatif',
        'to_path' => '//example.com/phishing',
        'status_code' => 301,
    ]);

    $this->get('/relatif')->assertRedirect(url('/example.com/phishing'));
});

it('refuses a target using a non http scheme', function () {
    $redirect = Redirect::factory()->create([
        'from_path' => '/piege',
        'to_path' => 'javascript:alert(1)',
        'status_code' => 301,
    ]);

    $this->get('/piege')->assertNotFound();

    expect($redirect->fresh()->hit_count)->toBe(0);
});

it('matches a source path saved without leading slash or with a trailing one', function (string $fromPath) {
    Redirect::factory()->create(['from_path' => $fromPath, 'to_path' => '/blog']);

    $this->get('/ancien-article')->assertRedirect(url('/blog'));
})->with(['ancien-article', '/ancien-article/', 'https://vivre-pleinement.fr/ancien-article']);

it('refuses an exotic target scheme and a duplicate source in the admin form', function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');
    Redirect::factory()->create(['from_path' => '/deja-la']);

    Livewire::test(CreateRedirect::class)
        ->fillForm(['from_path' => 'deja-la/', 'to_path' => 'javascript:alert(1)', 'status_code' => 301])
        ->call('create')
        ->assertHasFormErrors(['from_path', 'to_path']);
});
