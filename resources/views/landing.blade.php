<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ \App\Services\BrandingService::siteName() }} — All Digital Services in One Secure Platform</title>

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
                <a href="#services" class="transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">Features</a>
                <a href="#how-it-works" class="transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">How It Works</a>
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
                <a href="#services" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100" @click="open = false">Features</a>
                <a href="#how-it-works" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100" @click="open = false">How It Works</a>
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
        {{-- Hero --}}
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute -top-32 left-1/2 h-96 w-[46rem] -translate-x-1/2 rounded-full bg-brand-100/70 blur-3xl"></div>
            <div class="pointer-events-none absolute right-0 top-40 h-72 w-72 rounded-full bg-accent-100/70 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-24 bottom-0 h-72 w-72 rounded-full bg-brand-50 blur-3xl"></div>

            <div class="relative mx-auto grid max-w-6xl items-center gap-14 px-4 pb-16 pt-14 sm:px-6 sm:pt-20 lg:grid-cols-2 lg:pb-24 lg:pt-24">
                {{-- Left copy --}}
                <div class="text-center lg:text-left">
                    <span class="animate-fade-up inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-brand-700 shadow-sm">
                        <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span>
                        Trusted by 1,000+ users on {{ $siteName }}
                    </span>

                    <h1 class="animate-fade-up anim-delay-150 mx-auto mt-6 max-w-xl text-4xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-5xl lg:mx-0">
                        All Digital Services in One <span class="text-brand-600">Secure</span> Platform
                    </h1>

                    <p class="animate-fade-up anim-delay-300 mx-auto mt-5 max-w-lg text-base leading-relaxed text-slate-600 lg:mx-0">
                        Everything you need to verify, boost and grow — delivered fast and secure.
                    </p>

                    <ul class="animate-fade-up anim-delay-300 mx-auto mt-6 grid max-w-md grid-cols-1 gap-x-6 gap-y-2.5 text-left text-sm text-slate-700 sm:grid-cols-2 lg:mx-0">
                        @foreach ([
                            'Buy Virtual Numbers',
                            'Social Media Boost',
                            'Premium Tools',
                            'Digital Accounts',
                            'Fast Delivery',
                            'Secure Payments',
                        ] as $item)
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                                </svg>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>

                    <div class="animate-fade-up anim-delay-450 mt-9 flex flex-col items-center gap-3 sm:flex-row lg:justify-start lg:items-start lg:flex-row">
                        <x-button href="{{ route('register') }}" size="lg" class="w-full sm:w-auto">Get Started</x-button>
                        <x-button href="#services" variant="secondary" size="lg" class="w-full sm:w-auto">Explore Services</x-button>
                    </div>

                    <p class="animate-fade-up anim-delay-450 mt-5 flex items-center justify-center gap-1.5 text-xs text-slate-400 lg:justify-start">
                        <x-icon name="shield" class="h-3.5 w-3.5" />
                        No credit card required · Free to sign up
                    </p>
                </div>

                {{-- Right illustration --}}
                <div class="animate-fade-up anim-delay-300 relative mx-auto hidden h-[440px] w-full max-w-md sm:block">
                    <div class="absolute left-1/2 top-1/2 h-80 w-80 -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-br from-brand-200/60 to-brand-100/40 blur-2xl"></div>

                    {{-- Central wallet card --}}
                    <div class="absolute left-1/2 top-1/2 w-72 -translate-x-1/2 -translate-y-1/2 rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white shadow-2xl shadow-brand-600/30 ring-1 ring-white/20">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-brand-100">Wallet</span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white ring-1 ring-inset ring-white/20">
                                <x-icon name="check-badge" class="h-3 w-3" />
                                Verified
                            </span>
                        </div>
                        <p class="mt-5 text-3xl font-extrabold tracking-tight">₦24,500.00</p>
                        <p class="mt-1 text-xs text-brand-200">Available balance</p>
                        <div class="mt-6 grid grid-cols-2 gap-2">
                            <span class="flex items-center justify-center rounded-xl bg-white px-3 py-2 text-xs font-bold text-brand-700">Fund wallet</span>
                            <span class="flex items-center justify-center rounded-xl bg-white/15 px-3 py-2 text-xs font-bold text-white ring-1 ring-inset ring-white/20">Buy number</span>
                        </div>
                    </div>

                    {{-- Floating cards --}}
                    <div class="animate-float absolute -left-2 top-6 flex items-center gap-3 rounded-2xl border border-slate-100 bg-white/90 p-3.5 pr-5 shadow-xl shadow-slate-900/10 backdrop-blur">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <x-icon name="phone" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-900">Virtual Numbers</p>
                            <p class="text-xs text-slate-500">WhatsApp · Instant</p>
                        </div>
                    </div>

                    <div class="animate-float-slow absolute right-0 top-20 flex items-center gap-3 rounded-2xl border border-slate-100 bg-white/90 p-3.5 pr-5 shadow-xl shadow-slate-900/10 backdrop-blur">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent-50 text-accent-500">
                            <x-icon name="bolt" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-900">Social Boost</p>
                            <p class="text-xs text-slate-500">Instagram +1,000</p>
                        </div>
                    </div>

                    <div class="animate-float anim-delay-450 absolute bottom-16 -left-4 flex items-center gap-3 rounded-2xl border border-slate-100 bg-white/90 p-3.5 pr-5 shadow-xl shadow-slate-900/10 backdrop-blur">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <x-icon name="sparkles" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-900">Premium Tools</p>
                            <p class="text-xs text-slate-500">Unlocked now</p>
                        </div>
                    </div>

                    <div class="animate-float-slow anim-delay-300 absolute -right-2 bottom-4 flex items-center gap-3 rounded-2xl border border-slate-100 bg-white/90 p-3.5 pr-5 shadow-xl shadow-slate-900/10 backdrop-blur">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent-50 text-accent-500">
                            <x-icon name="users" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-900">Digital Accounts</p>
                            <p class="text-xs text-slate-500">Ready for you</p>
                        </div>
                    </div>
                </div>

                {{-- Mobile simplified illustration --}}
                <div class="sm:hidden">
                    <div class="mx-auto grid max-w-sm grid-cols-2 gap-3">
                        @foreach ([
                            ['icon' => 'phone', 'label' => 'Virtual Numbers', 'tint' => 'bg-brand-50 text-brand-600'],
                            ['icon' => 'bolt', 'label' => 'Social Boost', 'tint' => 'bg-accent-50 text-accent-500'],
                            ['icon' => 'sparkles', 'label' => 'Premium Tools', 'tint' => 'bg-brand-50 text-brand-600'],
                            ['icon' => 'users', 'label' => 'Digital Accounts', 'tint' => 'bg-accent-50 text-accent-500'],
                        ] as $card)
                            <div class="flex items-center gap-2.5 rounded-2xl border border-slate-100 bg-white p-3 shadow-sm">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $card['tint'] }}">
                                    <x-icon :name="$card['icon']" class="h-4.5 w-4.5" />
                                </span>
                                <span class="text-xs font-semibold text-slate-700">{{ $card['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="relative mx-auto max-w-6xl px-4 pb-16 sm:px-6 sm:pb-20">
                <div class="grid grid-cols-2 gap-4 rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm sm:grid-cols-4 sm:p-8">
                    @foreach ([
                        ['value' => '10K+', 'label' => 'Happy Users'],
                        ['value' => '500K+', 'label' => 'Orders Completed'],
                        ['value' => '99.9%', 'label' => 'Success Rate'],
                        ['value' => '24/7', 'label' => 'Support'],
                    ] as $stat)
                        <div class="text-center">
                            <p class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $stat['value'] }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Services --}}
        <section id="services" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Services</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Everything you need to verify &amp; grow</h2>
                <p class="mt-4 text-base text-slate-600">From one-time verification numbers to full account boosting, {{ $siteName }} has you covered.</p>
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
                            Learn more
                            <x-icon name="arrow-right" class="h-4 w-4" />
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Benefits / About --}}
        <section id="about" class="bg-gradient-to-b from-brand-50/60 to-white">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Why {{ $siteName }}</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Built to be trusted, priced to be loved</h2>
                    <p class="mt-4 text-base text-slate-600">We obsess over the details so you can focus on growing.</p>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['icon' => 'shield', 'title' => 'Secure', 'desc' => 'Bank-grade encryption and secure wallet payments on every order.'],
                        ['icon' => 'rocket', 'title' => 'Fast', 'desc' => 'Most orders are delivered in seconds — not hours.'],
                        ['icon' => 'check-badge', 'title' => 'Reliable', 'desc' => '99.9% success rate with automatic refunds when things go wrong.'],
                        ['icon' => 'banknotes', 'title' => 'Affordable', 'desc' => 'Transparent pricing with no hidden fees, ever.'],
                    ] as $benefit)
                        <div class="group rounded-2xl border border-slate-200/70 bg-white p-6 text-center shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-accent-50 text-accent-500 transition duration-300 group-hover:bg-accent-500 group-hover:text-white">
                                <x-icon :name="$benefit['icon']" class="h-6 w-6" />
                            </div>
                            <h3 class="mt-4 text-base font-bold text-slate-900">{{ $benefit['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $benefit['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section id="how-it-works" class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold uppercase tracking-wider text-brand-600">How it works</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Up and running in minutes</h2>
                <p class="mt-4 text-base text-slate-600">Three simple steps from sign-up to your first delivery.</p>
            </div>

            <div class="relative mt-12 grid gap-6 sm:grid-cols-3">
                <div class="absolute left-[16%] right-[16%] top-6 hidden border-t-2 border-dashed border-brand-200 sm:block"></div>
                @foreach ([
                    ['step' => '1', 'title' => 'Create an account', 'desc' => 'Sign up free with your email. No credit card required to get started.'],
                    ['step' => '2', 'title' => 'Fund your wallet', 'desc' => 'Top up securely and instantly — everything is processed from one wallet.'],
                    ['step' => '3', 'title' => 'Place an order', 'desc' => 'Buy numbers, boosts, or tools and get instant delivery to your dashboard.'],
                ] as $i => $step)
                    <div class="relative rounded-2xl border border-slate-200/70 bg-white p-7 text-center shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-600 text-lg font-extrabold text-white shadow-lg shadow-brand-600/30">
                            {{ $step['step'] }}
                        </span>
                        <h3 class="mt-5 text-lg font-bold text-slate-900">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- CTA --}}
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
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
                        <li><a href="#services" class="transition hover:text-brand-600">Services</a></li>
                        <li><a href="#how-it-works" class="transition hover:text-brand-600">How it works</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-900">Support</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-slate-500">
                        <li><a href="{{ $waHelp ?? '#contact' }}" {{ $waHelp ? 'target=_blank rel=noopener' : '' }} class="transition hover:text-brand-600">Help &amp; support</a></li>
                        <li><a href="#contact" class="transition hover:text-brand-600">Contact</a></li>
                        <li><a href="#" class="transition hover:text-brand-600">Terms of service</a></li>
                        <li><a href="#" class="transition hover:text-brand-600">Privacy policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 border-t border-slate-200/70 pt-8">
                <div x-data="{ subscribed: false }" class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
                    <form @submit.prevent="subscribed = true" class="flex w-full max-w-md items-center gap-2.5">
                        <div class="relative flex-1">
                            <x-icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-slate-400" />
                            <input type="email" required placeholder="Enter your email"
                                   class="block w-full rounded-full border-0 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                        </div>
                        <button type="submit" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                            Subscribe
                        </button>
                    </form>
                    <p x-show="subscribed" x-cloak x-transition.opacity class="text-sm font-medium text-emerald-600">
                        Thanks for subscribing!
                    </p>
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
