@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' · ' : '' }}{{ \App\Services\BrandingService::siteName() }}</title>

    @include('partials.brand-head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased" x-data="{ mobileMenuOpen: false }">
    <div class="flex min-h-full flex-col lg:flex-row">

        {{-- Desktop sidebar --}}
        <aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:flex lg:w-64 lg:flex-col">
            <div class="flex grow flex-col gap-y-5 bg-gradient-to-b from-brand-800 via-brand-900 to-brand-950 px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent-400">
                        <x-logo variant="wide" class="h-8 w-auto" />
                    </a>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-1.5">
                        @foreach ([
                            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                            ['route' => 'wallet', 'label' => 'Wallet', 'icon' => 'wallet'],
                            ['route' => 'numbers', 'label' => 'Buy Number', 'icon' => 'phone'],
                            ['route' => 'accounts', 'label' => 'Accounts', 'icon' => 'at'],
                            ['route' => 'boost', 'label' => 'Boost', 'icon' => 'bolt'],
                            ['route' => 'tools', 'label' => 'Buy Tools', 'icon' => 'sparkles'],
                            ['route' => 'orders', 'label' => 'My Orders', 'icon' => 'cart'],
                            ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell', 'unread' => auth()->user()?->id ? \App\Models\Notification::unreadCountFor(auth()->id()) : 0],
                            ['route' => 'referrals', 'label' => 'Referrals', 'icon' => 'users'],
                            ['route' => 'security', 'label' => 'Account Security', 'icon' => 'shield'],
                        ] as $item)
                            @php $active = request()->routeIs($item['route'] . '*'); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-400
                                   {{ $active ? 'bg-white/10 text-white' : 'text-brand-100/75 hover:bg-white/5 hover:text-white' }}">
                                    @if ($active)
                                        <span aria-hidden="true" class="absolute -left-6 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-accent-500"></span>
                                    @endif
                                    <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                    {{ $item['label'] }}
                                    @if (($item['unread'] ?? 0) > 0)
                                        <span class="ml-auto rounded-full bg-accent-500 px-2 py-0.5 text-[10px] font-bold leading-none text-white">{{ $item['unread'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                <div class="mt-auto border-t border-white/10 pt-4">
                    <x-profile-card />
                </div>
            </div>
        </aside>

        {{-- Mobile menu overlay --}}
        <div x-cloak x-show="mobileMenuOpen" x-transition.opacity
             class="fixed inset-0 z-30 bg-brand-950/50 backdrop-blur-sm lg:hidden" @click="mobileMenuOpen = false"></div>
        <aside x-cloak x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-gradient-to-b from-brand-800 via-brand-900 to-brand-950 lg:hidden">
            <div class="flex h-16 items-center justify-between px-6">
                <a href="{{ route('dashboard') }}" class="flex items-center focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent-400">
                    <x-logo variant="wide" class="h-8 w-auto" />
                </a>
                <button type="button" class="rounded-lg p-1.5 text-brand-100 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-400" @click="mobileMenuOpen = false" aria-label="Close menu">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-4 pb-6">
                <ul role="list" class="flex flex-col gap-1.5">
                    @foreach ([
                        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                        ['route' => 'wallet', 'label' => 'Wallet', 'icon' => 'wallet'],
                        ['route' => 'numbers', 'label' => 'Buy Number', 'icon' => 'phone'],
                        ['route' => 'accounts', 'label' => 'Accounts', 'icon' => 'at'],
                        ['route' => 'boost', 'label' => 'Boost', 'icon' => 'bolt'],
                        ['route' => 'tools', 'label' => 'Buy Tools', 'icon' => 'sparkles'],
                        ['route' => 'orders', 'label' => 'My Orders', 'icon' => 'cart'],
                        ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell', 'unread' => auth()->user()?->id ? \App\Models\Notification::unreadCountFor(auth()->id()) : 0],
                        ['route' => 'referrals', 'label' => 'Referrals', 'icon' => 'users'],
                        ['route' => 'security', 'label' => 'Account Security', 'icon' => 'shield'],
                    ] as $item)
                        @php $active = request()->routeIs($item['route'] . '*'); @endphp
                        <li>
                            <a href="{{ route($item['route']) }}" @click="mobileMenuOpen = false"
                               class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-400
                               {{ $active ? 'bg-white/10 text-white' : 'text-brand-100/75 hover:bg-white/5 hover:text-white' }}">
                                @if ($active)
                                    <span aria-hidden="true" class="absolute -left-4 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-accent-500"></span>
                                @endif
                                <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                {{ $item['label'] }}
                                @if (($item['unread'] ?? 0) > 0)
                                    <span class="ml-auto rounded-full bg-accent-500 px-2 py-0.5 text-[10px] font-bold leading-none text-white">{{ $item['unread'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            <div class="border-t border-white/10 p-4">
                <x-profile-card />
            </div>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
            <x-top-bar @toggle-mobile="mobileMenuOpen = !mobileMenuOpen" />

            <main class="flex-1 pb-28 pt-6 lg:pb-12">
                {{ $slot }}
            </main>

            <x-bottom-nav />
        </div>
    </div>

    <x-whatsapp-widget />

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
