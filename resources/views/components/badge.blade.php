@props([
    'tone' => 'brand',
])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'slate' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    ];
    $classes = $tones[$tone] ?? $tones['brand'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset $classes"]) }}>
    {{ $slot }}
</span>
