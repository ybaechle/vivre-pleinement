<?php

use App\Models\Appointment;
use App\Models\AppointmentService;

it('emits a single noindex robots tag on private pages', function (string $url, int $status) {
    $html = $this->get($url)->assertStatus($status)->getContent();

    expect(substr_count($html, 'name="robots"'))->toBe(1)
        ->and($html)->toMatch('/name="robots" content="noindex/')
        ->and(substr_count($html, 'name="description"'))->toBe(1)
        ->and(substr_count($html, 'rel="canonical"'))->toBe(1);
})->with([
    'contact thanks' => [fn () => route('contact.thanks'), 200],
    'booking service' => [fn () => route('booking.show', AppointmentService::factory()->create(['is_active' => true])->slug), 200],
    'booking management' => [fn () => route('booking.manage', Appointment::factory()->create()->token), 200],
    'booking reschedule' => [fn () => route('booking.reschedule', Appointment::factory()->create(['starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHour()])->token), 200],
    'missing page' => [fn () => '/page-qui-n-existe-pas', 404],
]);
