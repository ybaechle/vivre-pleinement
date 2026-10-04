@extends('layouts.page', ['withFooter' => false])

@php
    use Illuminate\Support\Number;

    $amountLabel = Number::currency($order->amount_cents / 100, in: 'EUR', locale: 'fr');
    $includesCoaching = $order->includesCoaching();
@endphp

@section('title', 'Paiement · '.$order->product->name)

@section('robots', 'noindex,nofollow')

@section('content')
    <main id="main" class="bg-cream-50 pt-32 pb-20 sm:pt-36">
        <div class="mx-auto max-w-xl px-4 sm:px-6">
            <h1 class="text-ink text-center font-serif text-3xl font-medium tracking-tight sm:text-4xl">
                Finalisez votre commande
            </h1>
            <p class="text-ink-soft mt-3 text-center text-sm">
                Paiement sécurisé. Votre lien de téléchargement part par email juste après.
            </p>

            <div class="ring-ink/5 mt-8 rounded-3xl bg-white p-6 shadow-xs ring-1">
                <p class="text-xs font-medium tracking-wider text-teal-700 uppercase">Votre commande</p>
                <div class="mt-3 flex items-baseline justify-between gap-4">
                    <p class="text-ink font-serif text-xl font-medium">{{ $order->product->name }}</p>
                    <p class="text-ink font-serif text-xl font-medium">{{ $amountLabel }}</p>
                </div>
                <p class="text-ink-soft mt-1 text-sm">
                    Référence {{ $order->reference }} · {{ $order->customer_email }}
                </p>
            </div>

            <x-stripe-payment-form :stripe-key="$stripeKey" :client-secret="$clientSecret" :amount-label="$amountLabel" :return-url="route('book.success', $order->token)">
                <x-slot:waiver>Je demande la mise à disposition immédiate du livre et reconnais renoncer expressément à mon droit de rétractation de 14 jours dès le téléchargement.</x-slot:waiver>

                <li class="inline-flex items-center gap-1.5">
                    <svg class="size-3.5 text-teal-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 13 4 4L19 7"/></svg>
                    @if ($includesCoaching)
                        Livre + coaching
                    @else
                        Téléchargement immédiat
                    @endif
                </li>
            </x-stripe-payment-form>

            <p class="mt-6 text-center text-sm">
                <a href="{{ route('book.show') }}" class="text-ink-muted hover:text-ink underline-offset-2 hover:underline">
                    ← Retour au livre
                </a>
            </p>
        </div>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/stripe-payment.js')
@endpush
