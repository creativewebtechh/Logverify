<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>About — {{ \App\Services\BrandingService::siteName() }}</title>

    @include('partials.brand-head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-page font-sans text-slate-800 antialiased">
    @php
        $siteName = \App\Services\BrandingService::siteName();
        $waHelp = \App\Services\WhatsAppService::link();
    @endphp

    {{-- Navbar --}}
    <header x-data="{ open: false }" id="top" class="sticky top-0 z-40 border-b border-slate-200/60 bg-white/85 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            <a href="/" class="flex items-center focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600" aria-label="{{ $siteName }} home">
                <x-logo variant="wide" class="h-9 w-auto" />
            </a>

            <nav class="hidden items-center gap-7 text-sm font-medium text-slate-600 md:flex">
                <a href="{{ route('about') }}" class="transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">About</a>
                <a href="{{ route('home') }}#services" class="transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">Features</a>
                <a href="{{ route('home') }}#how-it-works" class="transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">How It Works</a>
                <a href="{{ route('numbers') }}" class="transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">Buy Number</a>
                <a href="{{ route('tools') }}" class="transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">Tools</a>
            </nav>

            <div class="flex items-center gap-2.5">
                @auth
                    <x-button href="{{ route('dashboard') }}" size="sm">Dashboard</x-button>
                @else
                    <x-button href="{{ route('login') }}" variant="ghost" size="sm" class="hidden sm:inline-flex">Sign in</x-button>
                    <x-button href="{{ route('register') }}" size="sm">Sign up</x-button>
                @endauth

                <button type="button" class="rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 md:hidden" @click="open = !open" :aria-expanded="open" aria-label="Toggle menu">
                    <x-icon name="menu" x-show="!open" class="h-5 w-5" />
                    <x-icon name="x" x-show="open" x-cloak class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div x-cloak x-show="open" x-transition.opacity class="border-t border-slate-200/60 bg-white md:hidden">
            <nav class="mx-auto max-w-6xl space-y-1 px-4 py-4 sm:px-6">
                <a href="{{ route('about') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100" @click="open = false">About</a>
                <a href="{{ route('home') }}#services" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100" @click="open = false">Features</a>
                <a href="{{ route('home') }}#how-it-works" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100" @click="open = false">How It Works</a>
                <a href="{{ route('numbers') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100" @click="open = false">Buy Number</a>
                <a href="{{ route('tools') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100" @click="open = false">Tools</a>
                @auth
                    <div class="pt-2">
                        <x-button href="{{ route('dashboard') }}" class="w-full">Dashboard</x-button>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-2.5 pt-2">
                        <x-button href="{{ route('login') }}" variant="secondary" class="w-full">Sign in</x-button>
                        <x-button href="{{ route('register') }}" class="w-full">Sign up</x-button>
                    </div>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        {{-- Page hero --}}
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute -top-32 left-1/2 h-96 w-[46rem] -translate-x-1/2 rounded-full bg-brand-100/70 blur-3xl"></div>
            <div class="pointer-events-none absolute right-0 top-40 h-72 w-72 rounded-full bg-accent-100/70 blur-3xl"></div>

            <div class="relative mx-auto max-w-3xl px-4 pb-16 pt-16 text-center sm:px-6 sm:pt-24">
                <span class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-brand-700 shadow-sm">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span>
                    About {{ $siteName }}
                </span>
                <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-5xl">
                    Building the simplest way to <span class="text-brand-600">buy and grow</span> online
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-slate-600">
                    {{ $siteName }} is a one-stop digital services platform. We help you buy virtual numbers,
                    boost your social accounts, and unlock premium tools — all through one secure wallet.
                </p>
            </div>
        </section>

        {{-- Mission --}}
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Our mission</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Digital services, made effortless</h2>
                    <p class="mt-5 text-base leading-relaxed text-slate-600">
                        We believe getting verified and growing an online presence shouldn't be complicated.
                        That's why we've built a platform where every digital service lives in one place,
                        delivery is measured in seconds, and every order is backed by automatic refunds.
                    </p>
                    <p class="mt-4 text-base leading-relaxed text-slate-600">
                        From instant SMS verification numbers to reliable social boosts, we obsess over the details
                        so you can focus on what matters — your goals.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    @foreach ([
                        ['value' => '10K+', 'label' => 'Happy Users'],
                        ['value' => '500K+', 'label' => 'Orders Completed'],
                        ['value' => '99.9%', 'label' => 'Success Rate'],
                        ['value' => '24/7', 'label' => 'Support'],
                    ] as $stat)
                        <div class="rounded-2xl border border-slate-200/70 bg-white p-6 text-center shadow-sm">
                            <p class="text-3xl font-extrabold tracking-tight text-brand-600">{{ $stat['value'] }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Values --}}
        <section class="bg-gradient-to-b from-brand-50/60 to-white">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold uppercase tracking-wider text-brand-600">What we stand for</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Trusted by default</h2>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['icon' => 'shield', 'title' => 'Secure', 'desc' => 'Bank-grade encryption and secure wallet payments on every order.'],
                        ['icon' => 'rocket', 'title' => 'Fast', 'desc' => 'Most orders are delivered in seconds — not hours.'],
                        ['icon' => 'check-badge', 'title' => 'Reliable', 'desc' => '99.9% success rate with automatic refunds when things go wrong.'],
                        ['icon' => 'banknotes', 'title' => 'Affordable', 'desc' => 'Transparent pricing with no hidden fees, ever.'],
                    ] as $value)
                        <div class="rounded-2xl border border-slate-200/70 bg-white p-6 text-center shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-accent-50 text-accent-500">
                                <x-icon :name="$value['icon']" class="h-6 w-6" />
                            </div>
                            <h3 class="mt-4 text-base font-bold text-slate-900">{{ $value['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $value['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- What we offer --}}
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">What we offer</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Everything you need, in one place</h2>
            </div>

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['title' => 'Virtual Numbers', 'desc' => 'Virtual numbers for WhatsApp, Telegram and SMS verification delivered in seconds.', 'icon' => 'phone', 'route' => 'numbers'],
                    ['title' => 'Social Boost', 'desc' => 'Real engagement and growth for your accounts with fast, reliable boosts.', 'icon' => 'bolt', 'route' => 'boost'],
                    ['title' => 'Premium Tools', 'desc' => 'A growing toolkit of utilities to make managing your accounts effortless.', 'icon' => 'sparkles', 'route' => 'tools'],
                    ['title' => 'Digital Accounts', 'desc' => 'Ready-to-use social and platform accounts, delivered securely to your dashboard.', 'icon' => 'users', 'route' => 'accounts'],
                ] as $feature)
                    <a href="{{ route($feature['route']) }}"
                       class="group rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg hover:shadow-brand-600/10">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 transition duration-300 group-hover:scale-110 group-hover:bg-brand-600 group-hover:text-white">
                            <x-icon :name="$feature['icon']" class="h-6 w-6" />
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-slate-900">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $feature['desc'] }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 transition-all duration-300 group-hover:gap-2">
                            Explore
                            <x-icon name="arrow-right" class="h-4 w-4" />
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- CTA --}}
        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6 sm:pb-20">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 px-6 py-14 text-center shadow-2xl shadow-brand-600/30 sm:px-12">
                <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-20 -left-10 h-72 w-72 rounded-full bg-accent-500/30 blur-2xl"></div>

                <h2 class="relative text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Ready to get verified?</h2>
                <p class="relative mx-auto mt-4 max-w-xl text-base text-brand-50">
                    Join thousands of users verifying and growing with {{ $siteName }} today.
                </p>
                <div class="relative mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <x-button href="{{ route('register') }}" variant="orange" size="lg" class="w-full sm:w-auto">Create free account</x-button>
                    <x-button href="{{ route('login') }}" variant="secondary" size="lg" class="w-full bg-white/10 text-white ring-white/30 hover:bg-white/15 sm:w-auto">Sign in</x-button>
                </div>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer id="contact" class="border-t border-slate-200/70 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
            <div class="grid gap-10 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <a href="/" class="flex items-center gap-2.5 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600">
                        <x-logo class="h-8 w-8" />
                        <span class="text-base font-extrabold tracking-tight text-slate-900">{{ $siteName }}</span>
                    </a>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-500">
                        All digital services in one secure platform. Buy virtual numbers, boost social accounts, and unlock premium tools.
                    </p>
                    <div class="mt-5 flex items-center gap-2.5">
                        <a href="#" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-brand-600 hover:text-white" aria-label="Twitter / X">
                            <x-brand-icon name="twitter" class="h-4.5 w-4.5" />
                        </a>
                        <a href="#" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-brand-600 hover:text-white" aria-label="Telegram">
                            <x-brand-icon name="telegram" class="h-4.5 w-4.5" />
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-900">Company</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                        <li><a href="{{ route('about') }}" class="transition hover:text-brand-600">About</a></li>
                        <li><a href="{{ route('home') }}#services" class="transition hover:text-brand-600">Services</a></li>
                        <li><a href="{{ route('home') }}#how-it-works" class="transition hover:text-brand-600">How it works</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-900">Support</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                        <li><a href="{{ $waHelp ?? '#contact' }}" {{ $waHelp ? 'target=_blank rel=noopener' : '' }} class="transition hover:text-brand-600">Help &amp; support</a></li>
                        <li><a href="{{ route('home') }}#contact" class="transition hover:text-brand-600">Contact</a></li>
                        <li><a href="#" class="transition hover:text-brand-600">Terms of service</a></li>
                        <li><a href="#" class="transition hover:text-brand-600">Privacy policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 border-t border-slate-200/70 pt-8">
                <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
                    <p class="text-xs text-slate-400">
                        &copy; {{ now()->format('Y') }} {{ $siteName }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <x-whatsapp-widget docked />

    @livewireScriptConfig
</body>
</html>
