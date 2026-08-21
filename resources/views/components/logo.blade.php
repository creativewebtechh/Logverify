@props([
    'variant' => 'icon',
    'class' => null,
    'shadow' => false,
])

@php
    $custom = \App\Models\Setting::get('branding.logo');
    $default = $variant === 'wide' ? 'images/logo-wide.png' : 'images/logo.png';
    $src = filled($custom) && is_file(public_path($custom))
        ? $custom
        : (is_file(public_path($default)) ? $default : null);

    $size = filled($class)
        ? $class
        : ($variant === 'wide' ? 'h-8 w-auto' : 'h-8 w-8');
    $classes = trim($size.' object-contain select-none'.($shadow ? ' drop-shadow-sm' : ''));
@endphp

@if ($src)
    <img src="{{ asset($src) }}" alt="{{ \App\Services\BrandingService::siteName() }}" draggable="false" {{ $attributes->merge(['class' => $classes]) }}>
@else
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" {{ $attributes->merge(['class' => $classes]) }}>
        <defs>
            <linearGradient id="lgv-grad" x1="8" y1="6" x2="56" y2="58" gradientUnits="userSpaceOnUse">
                <stop stop-color="#60a5fa"/>
                <stop offset="0.55" stop-color="#3b82f6"/>
                <stop offset="1" stop-color="#2563eb"/>
            </linearGradient>
        </defs>
        <rect x="0.5" y="0.5" width="63" height="63" rx="15" fill="url(#lgv-grad)"/>
        <path d="M32 13.8C35.9 17.4 40.8 19.6 46.7 20.1C47 24.5 46 32.3 42.7 38.2C40.1 42.7 36.5 46.6 32 49.8C27.5 46.6 23.9 42.7 21.3 38.2C18 32.3 17 24.5 17.3 20.1C23.2 19.6 28.1 17.4 32 13.8Z" fill="#fff"/>
        <path d="M24.5 31.5 29.5 36.5 39.5 25.5" stroke="#2563eb" stroke-width="4.4" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M48.6 8.6v9.2M44 13.2h9.2M45.9 10.3l5.4 5.4M51.3 10.3l-5.4 5.4" stroke="#f97316" stroke-width="2.6" stroke-linecap="round"/>
    </svg>
@endif
