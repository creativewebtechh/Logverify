@props(['label' => null])

@php
    $tone = match ($label) {
        'Best Seller', 'Best Value', 'Featured', 'Popular' => 'brand',
        'Top Rated' => 'sky',
        'New' => 'slate',
        default => 'slate',
    };
@endphp

@if ($label)
    <x-badge :tone="$tone">{{ $label }}</x-badge>
@endif
