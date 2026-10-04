<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Video;

it('shows the related article block on the video page', function () {
    $post = Post::factory()->create(['slug' => 'mon-article', 'title' => 'Mon article lié']);
    $video = Video::factory()->create([
        'slug' => 'ma-video',
        'duration_seconds' => 600,
        'related_post_id' => $post->id,
    ]);

    $this->get('/videos/ma-video')
        ->assertOk()
        ->assertSee('À lire aussi')
        ->assertSee('Mon article lié');
});

it('shows a topically relevant video block on the article page via fallback', function () {
    $category = Category::factory()->create();
    $post = Post::factory()->create(['slug' => 'article-cat', 'title' => 'Vaincre la cardiophobie au quotidien']);
    $post->categories()->attach($category);

    $relevant = Video::factory()->create(['slug' => 'video-cardio', 'title' => 'La cardiophobie expliquée', 'duration_seconds' => 600, 'view_count' => 10]);
    $relevant->categories()->attach($category);

    $popular = Video::factory()->create(['slug' => 'video-popular', 'title' => 'Les antidépresseurs', 'duration_seconds' => 600, 'view_count' => 99999]);
    $popular->categories()->attach($category);

    expect($post->bestRelatedVideo()?->id)->toBe($relevant->id);

    $this->get('/blog/article-cat')
        ->assertOk()
        ->assertSee('La vidéo sur ce sujet');
});

it('shows no video block when no category video is topically relevant', function () {
    $category = Category::factory()->create();
    $post = Post::factory()->create(['slug' => 'article-orphelin', 'title' => 'La signification des rêves']);
    $post->categories()->attach($category);

    $offTopic = Video::factory()->create(['slug' => 'video-hs', 'title' => 'Les antidépresseurs et anxiolytiques', 'duration_seconds' => 600]);
    $offTopic->categories()->attach($category);

    expect($post->bestRelatedVideo())->toBeNull();

    $this->get('/blog/article-orphelin')
        ->assertOk()
        ->assertDontSee('La vidéo sur ce sujet');
});

it('prefers the explicitly linked video over the category fallback', function () {
    $category = Category::factory()->create();
    $post = Post::factory()->create(['slug' => 'article-pref']);
    $post->categories()->attach($category);

    $fallback = Video::factory()->create(['slug' => 'fallback', 'duration_seconds' => 600, 'view_count' => 9999]);
    $fallback->categories()->attach($category);

    $explicit = Video::factory()->create([
        'slug' => 'explicit',
        'duration_seconds' => 600,
        'view_count' => 1,
        'related_post_id' => $post->id,
    ]);

    expect($post->bestRelatedVideo()->id)->toBe($explicit->id);
});
