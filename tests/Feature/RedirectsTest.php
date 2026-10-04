<?php

use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Support\Facades\DB;

it('redirects an old post URL to the new blog URL', function () {
    Post::factory()->create(['slug' => 'burn-out', 'status' => 'published']);
    Redirect::create(['from_path' => '/burn-out', 'to_path' => '/blog/burn-out', 'status_code' => 301]);

    $this->get('/burn-out')->assertRedirect('/blog/burn-out')->assertStatus(301);
});

it('redirects regardless of a trailing slash (as indexed by Google)', function () {
    Redirect::create(['from_path' => '/burn-out', 'to_path' => '/blog/burn-out', 'status_code' => 301]);

    $this->get('/burn-out/')->assertRedirect('/blog/burn-out')->assertStatus(301);
});

it('redirects a paginated WordPress URL even with its Divi query string', function () {
    Redirect::create(['from_path' => '/blog/page/3', 'to_path' => '/blog?page=3', 'status_code' => 301]);

    $this->get('/blog/page/3/?et_blog')->assertStatus(301)->assertRedirect(url('/blog?page=3'));
});

it('preserves the URL fragment when redirecting to an internal anchor', function () {
    Redirect::create(['from_path' => '/ancienne-page', 'to_path' => '/#a-propos', 'status_code' => 301]);

    $this->get('/ancienne-page')
        ->assertStatus(301)
        ->assertRedirect(url('/#a-propos'));
});

it('tracks hit_count and last_hit_at in a single update query', function () {
    $redirect = Redirect::create(['from_path' => '/burn-out', 'to_path' => '/blog/burn-out', 'status_code' => 301]);

    DB::enableQueryLog();
    $this->get('/burn-out');
    $log = DB::getQueryLog();
    DB::disableQueryLog();

    $updateQueries = collect($log)->filter(fn ($q) => str_starts_with(strtolower($q['query']), 'update')
        && (str_contains($q['query'], 'redirects')));

    expect($updateQueries)->toHaveCount(1);

    $fresh = $redirect->fresh();
    expect($fresh->hit_count)->toBe(1)
        ->and($fresh->last_hit_at)->not->toBeNull();
});

it('does not redirect an unknown URL', function () {
    $this->get('/cette-url-nexiste-pas')->assertNotFound();
});
