@extends('layouts.page', ['withFooter' => false])

@php
    use App\Enums\AppointmentChannel;
    use Carbon\CarbonImmutable;
    use Illuminate\Support\Number;

    $start = CarbonImmutable::parse($appointment->starts_at);
@endphp

@section('title', 'Paiement · Vivre Pleinement')

@section('robots', 'noindex,nofollow')

@section('content')
    <main id="main" class="bg-cream-50 pt-32 pb-20 sm:pt-36">
        <div class="mx-auto max-w-xl px-4 sm:px-6">
            <h1 class="text-ink text-center font-serif text-3xl font-medium tracking-tight sm:text-4xl">
                Finalisez votre rendez-vous
            </h1>
            <p class="text-ink-soft mt-3 text-center text-sm">
                Paiement sécurisé. Vous ne serez débité·e qu'après confirmation.
            </p>

            <div class="ring-ink/5 mt-8 rounded-3xl bg-white p-6 shadow-xs ring-1">
                <p class="text-xs font-medium tracking-wider text-teal-700 uppercase">Votre rendez-vous</p>
                <div class="mt-3 flex items-baseline justify-between gap-4">
                    <p class="text-ink font-serif text-xl font-medium">{{ $appointment->service->name }}</p>
                    <p class="text-ink font-serif text-xl font-medium">{{ Number::currency($appointment->price_cents / 100, in: 'EUR', locale: 'fr') }}</p>
                </div>
                <p class="text-ink-soft mt-1 text-sm">
                    {{ $start->isoFormat('dddd D MMMM YYYY à H\hi') }} · {{ $appointment->service->duration_minutes }} min, {{ mb_strtolower($appointment->channel->getLabel()) }}
                </p>
            </div>

            @php $amountLabel = Number::currency($appointment->price_cents / 100, in: 'EUR', locale: 'fr'); @endphp

            <x-stripe-payment-form :stripe-key="$stripeKey" :client-secret="$clientSecret" :amount-label="$amountLabel" :return-url="route('booking.confirmation', $appointment->token)">
                <li class="inline-flex items-center gap-1.5">
                    <svg class="size-3.5 text-teal-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
                    Annulation gratuite
                </li>
                <li class="inline-flex items-center gap-1.5">
                    <svg class="size-3.5 text-teal-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
                    {{ $appointment->channel === AppointmentChannel::Phone ? 'Appel au numéro indiqué' : 'Lien visio envoyé après paiement' }}
                </li>
            </x-stripe-payment-form>

            <p class="mt-6 text-center text-sm">
                <a href="{{ route('booking.show', $appointment->service->slug) }}" class="text-ink-muted hover:text-ink underline-offset-2 hover:underline">
                    ← Changer de créneau
                </a>
            </p>
        </div>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/stripe-payment.js')
@endpush
