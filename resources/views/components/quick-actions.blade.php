@props([
    'actions' => [],
])

<div>
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Quick actions</h2>
    <div class="grid grid-cols-4 gap-3 sm:gap-4">
        @foreach ($actions as $action)
            <a href="{{ route($action['route']) }}" class="group flex flex-col items-center gap-2 rounded-2xl border border-slate-100 bg-white p-4 transition hover:-translate-y-0.5 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 transition group-hover:bg-brand-100">
                    <x-icon :name="$action['icon']" class="h-6 w-6" />
                </span>
                <span class="text-sm font-semibold text-slate-700">{{ $action['label'] }}</span>
            </a>
        @endforeach
    </div>
</div>
