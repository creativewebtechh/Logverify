@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' · ' : '' }}Admin · {{ \App\Services\BrandingService::siteName() }}</title>

    @include('partials.brand-head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-page antialiased">
    <div class="flex min-h-full">

        {{-- Admin sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col bg-gradient-to-b from-brand-800 via-brand-900 to-brand-950">
            <div class="flex h-16 shrink-0 items-center gap-2.5 border-b border-white/10 px-5">
                <x-logo class="h-8 w-8" />
                <div>
                    <p class="text-sm font-extrabold tracking-tight text-white">{{ \App\Services\BrandingService::siteName() }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-brand-200">Admin</p>
                </div>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <ul role="list" class="flex flex-col gap-1">
                    @foreach ([
                        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                        ['route' => 'admin.users', 'label' => 'Users', 'icon' => 'users'],
                        ['route' => 'admin.admins', 'label' => 'Admins', 'icon' => 'shield'],
                        ['route' => 'admin.sms', 'label' => 'SMS Dashboard', 'icon' => 'phone'],
                        ['route' => 'admin.number-services', 'label' => 'Number Services', 'icon' => 'key'],
                        ['route' => 'admin.accounts', 'label' => 'Accounts', 'icon' => 'at'],
                        ['route' => 'admin.tools', 'label' => 'Tools', 'icon' => 'sparkles'],
                        ['route' => 'admin.services', 'label' => 'Services', 'icon' => 'bolt'],
                        ['route' => 'admin.orders', 'label' => 'Orders', 'icon' => 'cart'],
                        ['route' => 'admin.transactions', 'label' => 'Transactions', 'icon' => 'receipt'],
                        ['route' => 'admin.webhooks', 'label' => 'Webhook Logs', 'icon' => 'clock'],
                        ['route' => 'admin.integrations', 'label' => 'Integrations', 'icon' => 'identification'],
                        ['route' => 'admin.settings', 'label' => 'Settings', 'icon' => 'cog'],
                    ] as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <li>
                            <a href="{{ route($item['route']) }}"
                               class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-400
                               {{ $active ? 'bg-white/10 text-white' : 'text-brand-100/75 hover:bg-white/5 hover:text-white' }}">
                                @if ($active)
                                    <span aria-hidden="true" class="absolute -left-3 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-accent-500"></span>
                                @endif
                                <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            <div class="border-t border-white/10 p-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-brand-100/75 transition hover:bg-white/5 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-400">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    Back to app
                </a>
            </div>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col pl-60">
            <div class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-100 bg-white/90 px-6 backdrop-blur-md">
                <h1 class="text-lg font-bold tracking-tight text-slate-900">Admin Panel</h1>
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600">Sign out</button>
                    </form>
                </div>
            </div>

            <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
