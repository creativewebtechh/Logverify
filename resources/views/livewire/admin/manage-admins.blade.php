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

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Admins</h1>
        <p class="text-sm text-slate-500">Create and manage administrator accounts</p>
    </div>

    <x-card>
        <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <x-icon name="shield" class="h-5 w-5" />
            </span>
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Create admin account</h2>
                <p class="mt-0.5 text-sm text-slate-500">Admins get full access to the admin panel. The account is email-verified immediately.</p>
            </div>
        </div>

        <form wire:submit="createAdmin" class="mt-6 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Full name</label>
                <input wire:model.live="name" id="name" type="text" autocomplete="name" placeholder="Jane Doe"
                       class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('name') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                @if ($errors->has('name'))
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('name') }}</p>
                @endif
            </div>

            <div>
                <label for="username" class="block text-sm font-medium text-slate-700">Username (optional)</label>
                <input wire:model.live="username" id="username" type="text" autocomplete="username" placeholder="janedoe"
                       class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('username') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                @if ($errors->has('username'))
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('username') }}</p>
                @endif
            </div>

            <div class="sm:col-span-2">
                <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                <input wire:model.live="email" id="email" type="email" autocomplete="email" placeholder="jane@example.com"
                       class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('email') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                @if ($errors->has('email'))
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('email') }}</p>
                @endif
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input wire:model.live="password" id="password" type="password" autocomplete="new-password" placeholder="At least 8 characters"
                       class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('password') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                @if ($errors->has('password'))
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('password') }}</p>
                @endif
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm password</label>
                <input wire:model.live="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" placeholder="Repeat your password"
                       class="mt-1.5 block w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset focus:bg-white focus:ring-2 focus:ring-inset focus:ring-brand-500 {{ $errors->has('password_confirmation') ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-200' }}">
                @if ($errors->has('password_confirmation'))
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $errors->first('password_confirmation') }}</p>
                @endif
            </div>

            <div class="sm:col-span-2">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="createAdmin">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span wire:loading.remove wire:target="createAdmin">Create admin</span>
                    <span wire:loading wire:target="createAdmin">Creating...</span>
                </x-button>
            </div>
        </form>
    </x-card>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Admin</th>
                        <th class="px-5 py-3">Joined</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($admins as $admin)
                        <tr>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">
                                        {{ strtoupper(substr($admin->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">
                                            {{ $admin->name }}
                                            @if ($admin->id === auth()->id())
                                                <span class="ml-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500">You</span>
                                            @endif
                                        </p>
                                        <p class="truncate text-xs text-slate-500">{{ $admin->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $admin->created_at?->format('M j, Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                @if ($admin->id !== auth()->id())
                                    <button type="button" wire:click="removeAdmin({{ $admin->id }})" wire:confirm="Remove admin access for {{ $admin->email }}?"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                        <x-icon name="shield" class="h-3.5 w-3.5" />
                                        Remove
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-12 text-center text-sm text-slate-500">No admin accounts yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
