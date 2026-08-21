<div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Notifications</h1>
            <p class="text-sm text-slate-500">Updates about your orders and wallet</p>
        </div>
        @if ($notifications->isNotEmpty())
            <x-button variant="secondary" size="sm" wire:click="markAllRead">
                Mark all read
            </x-button>
        @endif
    </div>

    <x-card :padding="false">
        <ul class="divide-y divide-slate-100">
            @forelse ($notifications as $n)
                <li class="flex items-start gap-4 px-5 py-4">
                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                        {{ $n->read_at ? 'bg-slate-100 text-slate-400' : 'bg-brand-50 text-brand-600' }}">
                        <x-icon :name="$n->read_at ? 'check' : ($n->type === 'success' ? 'check' : 'bell')" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $n->title }}</p>
                            @if (! $n->read_at)
                                <span class="h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                            @endif
                        </div>
                        @if ($n->message)
                            <p class="mt-0.5 text-sm text-slate-500">{{ $n->message }}</p>
                        @endif
                        <p class="mt-1 text-xs text-slate-400">{{ $n->created_at?->diffForHumans() }}</p>
                    </div>
                    @if ($n->action_url && ! $n->read_at)
                        <button type="button" wire:click="markRead({{ $n->id }})" class="shrink-0 text-xs font-semibold text-brand-600 hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                            Mark read
                        </button>
                    @endif
                </li>
            @empty
                <li class="px-5 py-16 text-center">
                    <x-icon name="bell" class="mx-auto h-10 w-10 text-slate-300" />
                    <p class="mt-3 text-sm font-medium text-slate-500">You're all caught up</p>
                    <p class="mt-1 text-xs text-slate-400">Notifications will appear here</p>
                </li>
            @endforelse
        </ul>

        @if ($notifications->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </x-card>
</div>
