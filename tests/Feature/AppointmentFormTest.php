<?php

use App\Enums\AppointmentChannel;
use App\Enums\AppointmentStatus;
use App\Filament\Admin\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Admin\Resources\Appointments\Pages\EditAppointment;
use App\Mail\AppointmentCancelled;
use App\Mail\AppointmentConfirmation;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');
});

it('renders the appointment create page', function () {
    $this->get(route('filament.admin.resources.appointments.create'))->assertOk();
});

it('auto-computes the end time from the service duration', function () {
    $service = AppointmentService::factory()->create(['duration_minutes' => 45]);
    $start = CarbonImmutable::now()->addDays(7)->setTime(10, 0);

    Livewire::test(CreateAppointment::class)
        ->set('data.appointment_service_id', $service->id)
        ->set('data.starts_at', $start->format('Y-m-d H:i:s'))
        ->assertSet('data.ends_at', $start->addMinutes(45)->format('Y-m-d H:i:s'));
});

it('defaults the channel to video', function () {
    Livewire::test(CreateAppointment::class)
        ->assertSet('data.channel', AppointmentChannel::Video->value);
});

it('submits the create form and persists an appointment with an auto-generated reference and token', function () {
    $service = AppointmentService::factory()->create(['duration_minutes' => 45]);
    $start = CarbonImmutable::now()->addDays(7)->setTime(10, 0);

    Livewire::test(CreateAppointment::class)
        ->fillForm([
            'appointment_service_id' => $service->id,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->addMinutes(45)->format('Y-m-d H:i:s'),
            'customer_first_name' => 'Camille',
            'customer_email' => 'camille@example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $appointment = Appointment::query()->firstOrFail();
    expect($appointment->reference)->not->toBeNull()
        ->and($appointment->token)->not->toBeNull();
});

it('rejects an overlapping appointment on the admin form', function () {
    $service = AppointmentService::factory()->create(['duration_minutes' => 60]);
    $start = CarbonImmutable::now()->addDays(7)->setTime(10, 0);

    Appointment::factory()->create([
        'appointment_service_id' => $service->id,
        'status' => AppointmentStatus::Confirmed,
        'starts_at' => $start,
        'ends_at' => $start->addMinutes(60),
    ]);

    Livewire::test(CreateAppointment::class)
        ->fillForm([
            'appointment_service_id' => $service->id,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->addMinutes(60)->format('Y-m-d H:i:s'),
            'customer_first_name' => 'Camille',
            'customer_email' => 'camille@example.com',
        ])
        ->call('create')
        ->assertHasFormErrors(['ends_at']);
});

it('does not let the stale checkout sweep cancel a pending appointment created by the admin', function () {
    Mail::fake();
    $service = AppointmentService::factory()->create(['duration_minutes' => 45]);
    $start = CarbonImmutable::now()->addDays(7)->setTime(10, 0);

    Livewire::test(CreateAppointment::class)
        ->fillForm([
            'appointment_service_id' => $service->id,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->addMinutes(45)->format('Y-m-d H:i:s'),
            'status' => AppointmentStatus::Pending,
            'customer_first_name' => 'Camille',
            'customer_email' => 'camille@example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->travel(1)->hour();
    $this->artisan('appointments:send-reminders')->assertSuccessful();

    expect(Appointment::query()->firstOrFail()->status)->toBe(AppointmentStatus::Pending);
    Mail::assertNothingQueued();
});

it('sends the confirmation when the admin creates a confirmed appointment', function () {
    Mail::fake();
    $service = AppointmentService::factory()->create(['duration_minutes' => 45]);
    $start = CarbonImmutable::now()->addDays(7)->setTime(10, 0);

    Livewire::test(CreateAppointment::class)
        ->fillForm([
            'appointment_service_id' => $service->id,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->addMinutes(45)->format('Y-m-d H:i:s'),
            'status' => AppointmentStatus::Confirmed,
            'customer_first_name' => 'Camille',
            'customer_email' => 'camille@example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    Mail::assertQueued(AppointmentConfirmation::class, fn ($mail) => $mail->hasTo('camille@example.com'));
});

it('cancels through the lifecycle when the admin switches the status in the form', function () {
    Mail::fake();
    $appointment = Appointment::factory()->create(['status' => AppointmentStatus::Confirmed]);

    Livewire::test(EditAppointment::class, ['record' => $appointment->getRouteKey()])
        ->fillForm(['status' => AppointmentStatus::Cancelled])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($appointment->fresh()->cancelled_at)->not->toBeNull();
    Mail::assertQueued(AppointmentCancelled::class, fn ($mail) => $mail->hasTo($appointment->customer_email));
});
