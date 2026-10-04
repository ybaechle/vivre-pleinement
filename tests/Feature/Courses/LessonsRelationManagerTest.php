<?php

use App\Filament\Admin\Resources\Modules\Pages\EditModule;
use App\Filament\Admin\Resources\Modules\RelationManagers\LessonsRelationManager;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');
});

it('creates a lesson with its slug and the provider detected from the pasted video url', function () {
    $module = Module::factory()->create();

    Livewire::test(LessonsRelationManager::class, [
        'ownerRecord' => $module,
        'pageClass' => EditModule::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'title' => 'La respiration apaisante',
            'video_id' => 'https://vimeo.com/123456789',
        ])
        ->assertHasNoFormErrors();

    $lesson = Lesson::query()->firstOrFail();

    expect($lesson->slug)->toStartWith('la-respiration-apaisante-')
        ->and($lesson->video_provider)->toBe('vimeo')
        ->and($lesson->video_id)->toBe('123456789');
});
