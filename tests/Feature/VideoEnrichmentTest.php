<?php

use App\Models\Video;

it('renders the intro above the video on the show page', function () {
    $video = Video::factory()->create([
        'slug' => 'avec-intro',
        'duration_seconds' => 600,
        'intro' => '<p>Texte introductif indexable.</p>',
    ]);

    $response = $this->get('/videos/avec-intro')->assertOk();

    $html = $response->getContent();
    $introPos = strpos($html, 'Texte introductif indexable');
    $videoPos = strpos($html, 'youtube-facade');

    expect($introPos)->not->toBeFalse()
        ->and($videoPos)->not->toBeFalse()
        ->and($introPos)->toBeLessThan($videoPos);
});
