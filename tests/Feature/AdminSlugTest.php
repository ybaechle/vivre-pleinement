<?php

use App\Filament\Admin\Resources\Tags\Pages\CreateTag;
use App\Filament\Admin\Resources\Tags\Pages\EditTag;
use App\Models\Tag;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');
});

it('fills the slug from the name on creation', function () {
    Livewire::test(CreateTag::class)
        ->set('data.name', 'Pleine conscience')
        ->assertSet('data.slug', 'pleine-conscience');
});

it('keeps the published slug when the name changes', function () {
    $tag = Tag::factory()->create(['name' => 'Ancien', 'slug' => 'ancien']);

    Livewire::test(EditTag::class, ['record' => $tag->getRouteKey()])
        ->set('data.name', 'Nouveau nom')
        ->assertSet('data.slug', 'ancien');
});
