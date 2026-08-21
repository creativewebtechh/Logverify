@props([
    'docked' => false,
])

@php
    $waLink = \App\Services\WhatsAppService::link();
    $waLabel = \App\Services\WhatsAppService::label();
    $position = $docked ? 'bottom-5 right-5 sm:bottom-[30px] sm:right-[30px]' : 'bottom-24 right-5 lg:bottom-[30px] lg:right-[30px]';
@endphp

@if ($waLink)
    <a href="{{ $waLink }}"
       target="_blank"
       rel="noopener noreferrer"
       class="group fixed {{ $position }} z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-emerald-600/30 transition duration-300 hover:scale-105 hover:shadow-xl hover:shadow-emerald-600/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#25D366] active:scale-95 sm:h-16 sm:w-16"
       aria-label="{{ $waLabel }}"
       title="{{ $waLabel }}">
        <span aria-hidden="true" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#25D366] opacity-20"></span>
        <x-brand-icon name="whatsapp" class="relative h-7 w-7 sm:h-8 sm:w-8" />
    </a>
@endif
