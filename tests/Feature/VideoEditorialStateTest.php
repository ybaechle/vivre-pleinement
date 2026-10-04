<?php

use App\Enums\VideoEditorialState;
use App\Filament\Admin\Resources\Videos\Pages\ListVideos;
use App\Models\User;
use App\Models\Video;
use Filament\Facades\Filament;
use Livewire\Livewire;

function videoWithEditorial(bool $enriched, bool $transcript): Video
{
    return Video::factory()->create([
        'intro' => $enriched ? '<p>Intro</p>' : null,
        'summary' => $enriched ? 'Résumé' : null,
        'transcript' => $transcript ? '<p>Texte</p>' : null,
    ]);
}

it('derives the editorial state from the intro, the summary and the transcript', function (bool $enriched, bool $transcript, VideoEditorialState $state) {
    expect(videoWithEditorial($enriched, $transcript)->editorialState())->toBe($state);
})->with([
    [true, true, VideoEditorialState::Complete],
    [true, false, VideoEditorialState::MissingTranscript],
    [false, true, VideoEditorialState::ToEnrich],
    [false, false, VideoEditorialState::ToDo],
]);

it('filters the admin video list with the same rules as the badge', function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');

    $complete = videoWithEditorial(true, true);
    $toEnrich = videoWithEditorial(false, true);
    $missingTranscript = videoWithEditorial(true, false);

    Livewire::test(ListVideos::class)
        ->filterTable('editorial', 'complete')
        ->assertCanSeeTableRecords([$complete])
        ->assertCanNotSeeTableRecords([$toEnrich, $missingTranscript])
        ->filterTable('editorial', 'to_enrich')
        ->assertCanSeeTableRecords([$toEnrich])
        ->assertCanNotSeeTableRecords([$complete, $missingTranscript])
        ->filterTable('editorial', 'no_transcript')
        ->assertCanSeeTableRecords([$missingTranscript])
        ->assertCanNotSeeTableRecords([$complete, $toEnrich]);
});
