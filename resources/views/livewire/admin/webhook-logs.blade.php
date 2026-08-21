<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Webhook Logs</h1>
            <p class="text-sm text-slate-500">Every payment gateway webhook delivery, including failures</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <select wire:model.live="gateway" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <option value="">All gateways</option>
                @foreach (['paystack', 'monnify'] as $g)
                    <option value="{{ $g }}">{{ ucfirst($g) }}</option>
                @endforeach
            </select>
            <select wire:model.live="status" class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <option value="">All statuses</option>
                @foreach (['received', 'processed', 'ignored', 'invalid_signature', 'failed'] as $s)
                    <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
            <input
                type="search"
                wire:model.live.debounce.400ms="search"
                placeholder="Search reference or event..."
                class="rounded-xl border-0 bg-white px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-brand-500"
            >
        </div>
    </div>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Gateway</th>
                        <th class="px-5 py-3">Event</th>
                        <th class="px-5 py-3">Reference</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Source IP</th>
                        <th class="px-5 py-3">Received</th>
                        <th class="px-5 py-3">Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-5 py-3">
                                <span class="font-semibold capitalize text-slate-900">{{ $log->gateway }}</span>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $log->event ?: '—' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $log->reference ?: '—' }}</td>
                            <td class="px-5 py-3">
                                <x-badge :tone="$log->status === 'processed' ? 'brand' : ($log->status === 'received' ? 'sky' : ($log->status === 'ignored' ? 'amber' : 'rose'))">
                                    {{ ucwords(str_replace('_', ' ', $log->status)) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ $log->source_ip ?: '—' }}</td>
                            <td class="px-5 py-3 text-xs text-slate-500">
                                {{ $log->created_at?->format('M j, Y g:i A') }}
                                @if ($log->processed_at)
                                    <p class="text-[10px] text-slate-400">processed {{ $log->processed_at->format('M j, Y g:i A') }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($log->payload)
                                    <details class="group">
                                        <summary class="cursor-pointer text-xs font-medium text-brand-600 hover:text-brand-700">View</summary>
                                        <pre class="mt-2 max-w-xs overflow-x-auto rounded-lg bg-slate-900 p-3 text-[10px] leading-relaxed text-slate-100">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">No webhook logs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $logs->links() }}
            </div>
        @endif
    </x-card>
</div>
