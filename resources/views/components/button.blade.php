@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-accent-500 text-white shadow-md shadow-accent-500/25 hover:bg-accent-600 hover:shadow-lg hover:shadow-accent-500/30 focus-visible:outline-accent-500',
        'orange' => 'bg-accent-500 text-white shadow-md shadow-accent-500/25 hover:bg-accent-600 hover:shadow-lg hover:shadow-accent-500/30 focus-visible:outline-accent-500',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50 hover:ring-slate-300 focus-visible:outline-slate-500',
        'outline' => 'bg-white text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50 hover:ring-slate-300 focus-visible:outline-slate-500',
        'danger' => 'bg-rose-600 text-white shadow-sm shadow-rose-600/20 hover:bg-rose-700 hover:shadow-md hover:shadow-rose-600/25 focus-visible:outline-rose-600',
        'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-slate-500',
        'dark' => 'bg-slate-900 text-white shadow-sm shadow-slate-900/20 hover:bg-slate-800 hover:shadow-md focus-visible:outline-slate-600',
    ];
    $sizes = [
        'xs' => 'px-3 py-1.5 text-xs',
        'sm' => 'px-3.5 py-2 text-sm',
        'md' => 'px-5 py-2.5 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];
    $classes = $variants[$variant] ?? $variants['primary'];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:shadow-none';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base $classes $sizeClasses"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$base $classes $sizeClasses"]) }}>
        {{ $slot }}
    </button>
@endif
