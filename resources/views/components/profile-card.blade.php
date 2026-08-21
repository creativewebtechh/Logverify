<div class="flex items-center gap-3">
    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 text-sm font-bold text-white ring-1 ring-white/20">
        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
    </span>
    <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
        <p class="truncate text-xs text-brand-200">Member since {{ auth()->user()->created_at?->format('M Y') }}</p>
    </div>
</div>
