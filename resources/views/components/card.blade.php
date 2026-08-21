@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-100 bg-white transition hover:shadow-sm']) }}>
    @if ($padding)
        <div class="p-5 sm:p-6">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
