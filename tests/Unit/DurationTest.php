<?php

use App\Support\Duration;

it('formats a duration as a clock, with hours only when needed', function (int $seconds, string $expected) {
    expect(Duration::clock($seconds))->toBe($expected);
})->with([
    [0, '0:00'],
    [245, '4:05'],
    [3723, '1:02:03'],
]);
