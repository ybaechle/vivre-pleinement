<?php

use Illuminate\Console\Scheduling\Schedule;

it('purges the expired student password reset tokens every day', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_ends_with((string) $event->command, 'auth:clear-resets students'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *');
});
