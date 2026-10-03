<?php

use App\Filament\Admin\Resources\Videos\Pages\EditVideo;
use App\Models\User;
use App\Models\Video;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('orders the chapters by start time whatever the input order', function () {
    $video = Video::factory()->create([
        'duration_seconds' => 600,
        'chapters' => [
            ['title' => 'Deuxième', 'start_seconds' => 120],
            ['title' => 'Premier', 'start_seconds' => 0],
        ],
    ]);

    expect($video->chaptersForSchema())->sequence(
        fn ($chapter) => $chapter->toMatchArray(['name' => 'Premier', 'startOffset' => 0, 'endOffset' => 120]),
        fn ($chapter) => $chapter->toMatchArray(['name' => 'Deuxième', 'startOffset' => 120, 'endOffset' => 600]),
    );
});

it('refuses chapters that do not start at zero in the admin form', function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');
    $video = Video::factory()->create();

    Livewire::test(EditVideo::class, ['record' => $video->getRouteKey()])
        ->fillForm(['chapters' => [['title' => 'Introduction', 'start_seconds' => 15]]])
        ->call('save')
        ->assertHasFormErrors(['chapters']);
});
