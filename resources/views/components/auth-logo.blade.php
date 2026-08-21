@props([
    'class' => null,
])

<div class="mb-6 flex justify-center {{ $class }}">
    <a href="{{ route('home') }}" class="focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600">
        <x-logo variant="wide" class="h-9 w-auto" shadow />
    </a>
</div>
