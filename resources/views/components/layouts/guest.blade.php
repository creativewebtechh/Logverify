@props([
    'title' => null,
    'cardClass' => 'max-w-md',
])

@php
    $siteName = \App\Services\BrandingService::siteName();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' · ' : '' }}{{ $siteName }}</title>

    @include('partials.brand-head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-page font-sans text-slate-800 antialiased">
    <div class="flex min-h-screen">

        {{-- Left branding panel --}}
        <aside class="relative hidden w-1/2 shrink-0 overflow-hidden lg:block">
            <div class="relative flex min-h-full flex-col justify-between bg-gradient-to-br from-brand-600 via-brand-700 to-brand-950 px-12 py-14 text-white">
                <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-accent-500/20 blur-3xl"></div>
                <div class="pointer-events-none absolute right-10 top-1/3 h-40 w-40 rounded-full bg-brand-400/20 blur-2xl"></div>

                <a href="{{ route('home') }}" class="relative w-fit focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                    <x-logo variant="wide" class="h-10 w-auto drop-shadow-lg" shadow />
                </a>

                <div class="relative my-10">
                    <h2 class="max-w-md text-3xl font-extrabold leading-tight tracking-tight">
                        All Digital Services in One Secure Platform
                    </h2>
                    <p class="mt-3 max-w-md text-sm leading-relaxed text-brand-100/90">
                        Buy virtual numbers, boost social accounts, unlock premium tools and manage everything from one fast, secure wallet.
                    </p>

                    <ul class="mt-8 space-y-3.5">
                        @foreach ([
                            'Secure payments you can trust',
                            'Fast delivery in seconds',
                            'Reliable 24/7 support',
                            'Instant wallet top-ups',
                        ] as $benefit)
                            <li class="flex items-center gap-3 text-sm font-medium text-brand-50">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/15 ring-1 ring-inset ring-white/25">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                                    </svg>
                                </span>
                                {{ $benefit }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="relative flex items-center gap-3 text-sm text-brand-100/80">
                    <div class="flex -space-x-2">
                        @foreach (['bg-brand-300', 'bg-brand-200', 'bg-accent-300'] as $i => $avatar)
                            <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $avatar }} text-[10px] font-bold text-brand-950 ring-2 ring-brand-900"></span>
                        @endforeach
                    </div>
                    <p>Trusted by <span class="font-semibold text-white">1,000+</span> happy users</p>
                </div>
            </div>
        </aside>

        {{-- Right auth card --}}
        <main class="flex w-full flex-col items-center justify-center px-4 py-10 sm:px-6 lg:w-1/2 lg:px-12">
            <div class="w-full {{ $cardClass }}">
                <div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">
                    &copy; {{ now()->format('Y') }} {{ $siteName }}. All rights reserved.
                </p>
            </div>
        </main>
    </div>

    <x-whatsapp-widget docked />

    @livewireScriptConfig
</body>
</html>
