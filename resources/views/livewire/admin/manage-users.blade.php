<div class="space-y-6">
    @if (session('success'))
        <div class="rounded-xl bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700 ring-1 ring-inset ring-brand-600/15">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-inset ring-rose-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Users</h1>
            <p class="text-sm text-slate-500">Manage accounts, roles and verification</p>
        </div>
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Search name, email or code..."
            class="w-full rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:w-72"
        >
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Wallet</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Verified</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ $user->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-900">
                                {{ \App\Support\Money::format($user->wallet?->balance ?? 0) }}
                            </td>
                            <td class="px-5 py-3">
                                <button type="button" wire:click="toggleRole({{ $user->id }})" class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->role === 'admin' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($user->role) }}
                                </button>
                            </td>
                            <td class="px-5 py-3">
                                <button type="button" wire:click="toggleVerified({{ $user->id }})" class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_verified ? 'bg-brand-100 text-brand-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $user->is_verified ? 'Verified' : 'Unverified' }}
                                </button>
                            </td>
                            <td class="px-5 py-3">
                                <button type="button" wire:click="toggleStatus({{ $user->id }})" class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status ? 'bg-brand-100 text-brand-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $user->status ? 'Active' : 'Suspended' }}
                                </button>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span class="text-xs text-slate-400">{{ $user->created_at?->format('M j, Y') }}</span>
                                    <button type="button" wire:click="delete({{ $user->id }})" wire:confirm="Delete this user and their data? This cannot be undone."
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">No users found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>
</div>
