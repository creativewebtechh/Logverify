@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'brand',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-100 bg-white p-5 transition hover:shadow-sm']) }}>
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        @if ($icon)
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <x-icon :name="$icon" class="h-4.5 w-4.5" />
            </span>
        @endif
    </div>
    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
</div>
