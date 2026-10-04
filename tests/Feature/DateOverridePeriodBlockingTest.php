<?php

use App\Filament\Admin\Resources\DateOverrides\Pages\CreateDateOverride;
use App\Filament\Admin\Resources\DateOverrides\Pages\ListDateOverrides;
use App\Models\DateOverride;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');
});

it('blocks every day of the period in one go', function () {
    $from = CarbonImmutable::now()->addDays(10)->startOfDay();

    Livewire::test(ListDateOverrides::class)
        ->callAction('blockPeriod', [
            'from' => $from->toDateString(),
            'to' => $from->addDays(4)->toDateString(),
            'reason' => 'Congés',
        ]);

    expect(DateOverride::query()->count())->toBe(5)
        ->and(DateOverride::query()->first()->isFullDay())->toBeTrue();
});

it('does not duplicate a day that is already blocked the same way', function () {
    $from = CarbonImmutable::now()->addDays(10)->startOfDay();

    DateOverride::factory()->closed()->create([
        'date' => $from->toDateString(),
    ]);

    Livewire::test(ListDateOverrides::class)
        ->callAction('blockPeriod', [
            'from' => $from->toDateString(),
            'to' => $from->addDay()->toDateString(),
        ]);

    expect(DateOverride::query()->count())->toBe(2);
});

it('keeps the time range when the period is only a partial closure', function () {
    $from = CarbonImmutable::now()->addDays(10)->startOfDay();

    Livewire::test(ListDateOverrides::class)
        ->callAction('blockPeriod', [
            'from' => $from->toDateString(),
            'to' => $from->addDay()->toDateString(),
            'start_time' => '12:00',
            'end_time' => '14:00',
        ]);

    expect(DateOverride::query()->count())->toBe(2)
        ->and(DateOverride::query()->first()->isFullDay())->toBeFalse();
});

it('refuses an end date before the start date', function () {
    $from = CarbonImmutable::now()->addDays(10)->startOfDay();

    Livewire::test(ListDateOverrides::class)
        ->callAction('blockPeriod', [
            'from' => $from->toDateString(),
            'to' => $from->subDays(3)->toDateString(),
        ])
        ->assertHasActionErrors(['to']);

    expect(DateOverride::query()->count())->toBe(0);
});

it('blocks the current day from the quick action', function () {
    Livewire::test(ListDateOverrides::class)
        ->callAction('blockToday');

    expect(DateOverride::query()
        ->whereDate('date', CarbonImmutable::now()->toDateString())
        ->exists())->toBeTrue();
});

it('refuses a partial closure with only one of its two times', function (array $times) {
    $from = CarbonImmutable::now()->addDays(10)->startOfDay();

    Livewire::test(ListDateOverrides::class)
        ->callAction('blockPeriod', array_merge([
            'from' => $from->toDateString(),
            'to' => $from->toDateString(),
        ], $times))
        ->assertHasFormErrors();

    expect(DateOverride::query()->count())->toBe(0);
})->with([
    'start only' => [['start_time' => '09:00']],
    'end only' => [['end_time' => '12:00']],
]);

it('refuses a single blocking with only one of its two times', function (array $times) {
    Livewire::test(CreateDateOverride::class)
        ->fillForm(array_merge(['date' => CarbonImmutable::now()->addDays(10)->toDateString()], $times))
        ->call('create')
        ->assertHasFormErrors();

    expect(DateOverride::query()->count())->toBe(0);
})->with([
    'start only' => [['start_time' => '09:00']],
    'end only' => [['end_time' => '12:00']],
]);

it('labels a full-day blocking and does not flag today as past', function () {
    DateOverride::factory()->create(['date' => today(), 'start_time' => null, 'end_time' => null]);

    Livewire::test(ListDateOverrides::class)
        ->assertSee('Journée entière')
        ->assertDontSee('Passé');
});
