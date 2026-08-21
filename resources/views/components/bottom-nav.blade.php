<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-100 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-md lg:hidden" aria-label="Primary">
    <div class="relative grid grid-cols-5 px-2 pt-1.5">

        {{-- Raised center action --}}
        <a href="{{ route('boost') }}"
           class="absolute left-1/2 top-0 z-10 flex -translate-x-1/2 -translate-y-7 flex-col items-center justify-center gap-0.5 rounded-full bg-gradient-to-br from-brand-600 to-brand-800 text-white shadow-lg shadow-brand-900/25 ring-4 ring-page transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 active:scale-95">
            <span class="flex h-14 w-14 flex-col items-center justify-center">
                <x-icon name="fire" class="h-6 w-6" />
                <span class="text-[10px] font-bold leading-none">Boost</span>
            </span>
        </a>

        @foreach ([
            ['route' => 'numbers', 'label' => 'Buy Number', 'icon' => 'phone'],
            ['route' => 'wallet', 'label' => 'Wallet', 'icon' => 'wallet'],
            ['route' => 'accounts', 'label' => 'Accounts', 'icon' => 'at'],
            ['route' => 'tools', 'label' => 'Tools', 'icon' => 'sparkles'],
        ] as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}"
               class="relative flex flex-col items-center gap-1 rounded-xl px-1 py-2 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600
               {{ $active ? 'text-brand-700' : 'text-slate-500 hover:text-slate-700' }}">
                @if ($active)
                    <span aria-hidden="true" class="absolute top-0 h-1 w-1 rounded-full bg-accent-500"></span>
                @endif
                <x-icon :name="$item['icon']" class="h-5 w-5" />
                <span class="text-[11px] font-semibold">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
