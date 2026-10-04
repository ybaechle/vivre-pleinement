<?php

use App\Filament\Admin\Resources\AppointmentServices\Pages\CreateAppointmentService;
use App\Filament\Admin\Resources\Courses\Pages\CreateCourse;
use App\Filament\Admin\Resources\Products\Pages\CreateProduct;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');
});

it('refuses a sale price below the Stripe minimum', function (string $page) {
    Livewire::test($page)
        ->fillForm(['price' => 0.3])
        ->call('create')
        ->assertHasFormErrors(['price']);
})->with([
    'course' => CreateCourse::class,
    'product' => CreateProduct::class,
    'service' => CreateAppointmentService::class,
]);

it('keeps the free service at 0 € allowed', function () {
    Livewire::test(CreateAppointmentService::class)
        ->fillForm(['price' => 0])
        ->call('create')
        ->assertHasNoFormErrors(['price']);
});
