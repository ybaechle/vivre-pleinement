<?php

use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\BookOrders\Pages\ListBookOrders;
use App\Filament\Admin\Resources\Enrollments\Pages\ListEnrollments;
use App\Models\BookOrder;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('admin');
});

it('totals only the paid book orders', function () {
    BookOrder::factory()->paid()->create(['amount_cents' => 3700]);
    BookOrder::factory()->create(['amount_cents' => 7000]);
    BookOrder::factory()->refunded()->create(['amount_cents' => 7000]);

    Livewire::test(ListBookOrders::class)
        ->assertTableColumnSummarySet('amount_cents', 'total', 3700);
});

it('totals only the active enrollments', function () {
    Enrollment::factory()->create(['amount_paid_cents' => 14900]);
    Enrollment::factory()->create(['amount_paid_cents' => 9900, 'status' => EnrollmentStatus::Refunded]);

    Livewire::test(ListEnrollments::class)
        ->assertTableColumnSummarySet('amount_paid_cents', 'total', 14900);
});

it('links paid rows to Stripe and leaves offered ones without link', function () {
    $paid = Enrollment::factory()->create(['stripe_payment_intent_id' => 'pi_paye']);
    Enrollment::factory()->create(['stripe_payment_intent_id' => null]);

    Livewire::test(ListEnrollments::class)
        ->assertCanSeeTableRecords([$paid])
        ->assertSee('https://dashboard.stripe.com/payments/pi_paye', false);
});
