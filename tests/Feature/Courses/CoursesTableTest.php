<?php

use App\Filament\Admin\Resources\Courses\Pages\ListCourses;
use App\Models\Course;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');
});

it('sorts the courses by price', function () {
    $cheap = Course::factory()->create(['price_cents' => 4900]);
    $expensive = Course::factory()->create(['price_cents' => 19900]);

    Livewire::test(ListCourses::class)
        ->sortTable('price_cents', 'desc')
        ->assertCanSeeTableRecords([$expensive, $cheap], inOrder: true)
        ->assertSee('199,00');
});
