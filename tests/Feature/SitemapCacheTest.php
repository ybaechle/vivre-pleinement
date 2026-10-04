<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;

it('flushes the sitemap cache when a category changes', function () {
    Cache::put('sitemap.urls', ['cached'], now()->addHour());

    Category::factory()->create();

    expect(Cache::has('sitemap.urls'))->toBeFalse();
});

it('flushes the sitemap cache when a tag changes', function () {
    Cache::put('sitemap.urls', ['cached'], now()->addHour());

    Tag::factory()->create();

    expect(Cache::has('sitemap.urls'))->toBeFalse();
});

it('flushes the sitemap cache when a course changes', function () {
    Cache::put('sitemap.urls', ['cached'], now()->addHour());

    Course::factory()->create();

    expect(Cache::has('sitemap.urls'))->toBeFalse();
});

it('lists the indexable legal pages in the sitemap', function () {
    Cache::flush();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(route('legal.mentions'), false)
        ->assertSee(route('legal.privacy'), false)
        ->assertSee(route('legal.cookies'), false)
        ->assertSee(route('legal.cgv'), false);
});

it('keeps legal pages indexable (no robots noindex)', function () {
    $this->get('/mentions-legales')
        ->assertOk()
        ->assertDontSee('noindex', false);
});

/**
 * Une page vide annoncée à Google dessert le reste du site : l'index des
 * formations n'entre au sitemap qu'une fois une formation publiée.
 */
it('omits the courses index from the sitemap while none is published', function () {
    Course::query()->forceDelete();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertDontSee(route('courses.index'), false);
});

it('lists the courses index once a course is published', function () {
    Course::factory()->create();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee(route('courses.index'), false);
});
