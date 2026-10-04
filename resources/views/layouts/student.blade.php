@extends('layouts.page')

@section('robots', 'noindex, nofollow')

@section('content')
    <main id="main" class="bg-cream-50 flex min-h-[100dvh] flex-col pt-32 pb-16 sm:pt-36 sm:pb-20">
        @yield('student')
    </main>
@endsection
