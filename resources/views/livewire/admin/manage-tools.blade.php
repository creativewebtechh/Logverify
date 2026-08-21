<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tools</h1>
            <p class="text-sm text-slate-500">Manage the tools catalogue — manual inventory with stock and demo images</p>
        </div>
        <x-button variant="orange" wire:click="startAdd" class="justify-center">
            <x-icon name="plus" class="h-4 w-4" />
            Add tool
        </x-button>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700">
            {{ session('success') }}
        </div>
    @endif

    <x-card>
        @if ($editingId)
            <div class="mb-4 flex items-center justify-between rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3">
                <p class="text-sm font-medium text-brand-700">You are editing an existing tool.</p>
                <button type="button" wire:click="startAdd" class="text-sm font-semibold text-brand-700 underline-offset-2 hover:underline">
                    Cancel editing
                </button>
            </div>
        @endif

        <h2 class="text-sm font-semibold text-slate-900">{{ $editingId ? 'Edit tool' : 'Add a tool' }}</h2>
        <form wire:submit="add" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <input wire:model="name" placeholder="Name" class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                @error('name')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <input wire:model="slug" placeholder="slug (auto-generated)" class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                @error('slug')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <textarea wire:model="description" placeholder="Description" rows="2" class="rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500 sm:col-span-2"></textarea>
            <div>
                <input wire:model="price" type="number" step="0.01" min="0" placeholder="Price (NGN)" class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                @error('price')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <input wire:model="category" placeholder="Category (api, automation, ai, design)" class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                @error('category')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <input wire:model="stock" type="number" min="0" placeholder="Stock (units)" class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                @error('stock')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <input wire:model="download_url" type="url" placeholder="Download URL (optional)" class="w-full rounded-xl border-0 bg-slate-50 px-3.5 py-2.5 text-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-brand-500">
                @error('download_url')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="flex items-center gap-3">
                    <span class="group flex cursor-pointer items-center gap-2.5 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-brand-400 hover:text-brand-600">
                        <x-icon name="upload" class="h-4 w-4" />
                        {{ $image ? $image->getClientOriginalName() : ($existingImage ? 'Replace image' : 'Upload demo image') }}
                        <input type="file" wire:model="image" accept="image/png,image/jpeg,image/webp" class="sr-only">
                    </span>
                    @if ($image)
                        <span class="flex h-12 w-12 overflow-hidden rounded-lg ring-1 ring-slate-200">
                            <img src="{{ $image->temporaryUrl() }}" alt="New demo image" class="h-full w-full object-cover">
                        </span>
                    @elseif ($existingImage)
                        <span class="flex h-12 w-12 overflow-hidden rounded-lg ring-1 ring-slate-200">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($existingImage) }}" alt="Current demo image" class="h-full w-full object-cover">
                        </span>
                    @endif
                </label>
                @error('image')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="flex cursor-pointer items-center gap-2.5">
                    <input type="checkbox" wire:model="featured" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span class="text-sm font-semibold text-slate-700">Feature this tool</span>
                    <span class="text-xs text-slate-500">Featured tools are highlighted on the tools page.</span>
                </label>
            </div>
            <x-button type="submit" class="sm:col-span-2">
                <x-icon name="check" class="h-4 w-4" />
                {{ $editingId ? 'Update tool' : 'Add tool' }}
            </x-button>
        </form>
        @if ($errors->any())
            <ul class="mt-3 space-y-1 text-xs font-medium text-rose-600">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Tool</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3">Price</th>
                        <th class="px-5 py-3">Stock</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tools as $tool)
                        <tr>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                                        @if ($tool->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($tool->image))
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($tool->image) }}" alt="{{ $tool->name }}" class="h-full w-full object-cover">
                                        @else
                                            <x-icon name="sparkles" class="h-5 w-5 text-slate-400" />
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <p class="font-semibold text-slate-900">{{ $tool->name }}</p>
                                            @if ($tool->featured)
                                                <x-badge tone="amber">Featured</x-badge>
                                            @endif
                                        </div>
                                        <p class="max-w-[280px] truncate text-xs text-slate-500">{{ $tool->description }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ ucfirst($tool->category) }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ \App\Support\Money::format($tool->price) }}</td>
                            <td class="px-5 py-3 text-xs font-medium text-slate-500">{{ $tool->stock }}</td>
                            <td class="px-5 py-3">
                                <x-badge :tone="$tool->status === 'active' ? 'brand' : 'slate'">{{ ucfirst($tool->status) }}</x-badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button type="button" wire:click="edit({{ $tool->id }})" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Edit</button>
                                <button type="button" wire:click="toggleStatus({{ $tool->id }})" class="ml-3 text-xs font-semibold text-brand-600 hover:text-brand-700">{{ $tool->status === 'active' ? 'Disable' : 'Enable' }}</button>
                                <button type="button" wire:click="delete({{ $tool->id }})" wire:confirm="Delete this tool?" class="ml-3 text-xs font-semibold text-rose-600 hover:text-rose-700">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">No tools yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tools->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $tools->links() }}
            </div>
        @endif
    </x-card>
</div>
