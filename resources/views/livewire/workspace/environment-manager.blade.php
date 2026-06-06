@php
    $cell = 'rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp
<div>
    <button wire:click="open"
        class="inline-flex items-center gap-1 rounded-md bg-gray-100 dark:bg-gray-700 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
        Environments
    </button>

    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="close"></div>
            <div class="relative w-full max-w-2xl rounded-lg bg-white dark:bg-gray-800 shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-5 py-3">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Environments</h2>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 divide-x divide-gray-100 dark:divide-gray-700">
                    {{-- List --}}
                    <div class="p-4 space-y-2 max-h-96 overflow-y-auto">
                        @forelse ($environments as $environment)
                            <div wire:key="env-{{ $environment->id }}"
                                class="rounded-md border border-gray-200 dark:border-gray-700 p-3 {{ $editingId === $environment->id ? 'ring-2 ring-indigo-400' : '' }}">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $environment->name }}</span>
                                    @if ($environment->is_active)
                                        <span class="text-[10px] font-semibold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 px-2 py-0.5">ACTIVE</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-xs text-gray-400">{{ count($environment->variables ?? []) }} variables</div>
                                <div class="mt-2 flex gap-3 text-xs">
                                    <button wire:click="edit({{ $environment->id }})" class="text-indigo-600 dark:text-indigo-400 hover:underline">Edit</button>
                                    @unless ($environment->is_active)
                                        <button wire:click="makeActive({{ $environment->id }})" class="text-gray-500 dark:text-gray-400 hover:underline">Set active</button>
                                    @endunless
                                    <button wire:click="delete({{ $environment->id }})" wire:confirm="Delete this environment?" class="text-rose-600 dark:text-rose-400 hover:underline ml-auto">Delete</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 italic">No environments yet.</p>
                        @endforelse
                    </div>

                    {{-- Form --}}
                    <div class="p-4 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $editingId ? 'Edit environment' : 'New environment' }}</h3>
                        <div>
                            <input type="text" wire:model="name" placeholder="e.g. Development" class="{{ $cell }} w-full">
                            @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Variables</span>
                                <button type="button" wire:click="addVariable" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">+ Add</button>
                            </div>
                            @foreach ($variableRows as $i => $row)
                                <div wire:key="var-{{ $i }}" class="grid grid-cols-12 gap-1">
                                    <input type="text" wire:model="variableRows.{{ $i }}.key" placeholder="KEY" class="col-span-5 {{ $cell }} font-mono">
                                    <input type="text" wire:model="variableRows.{{ $i }}.value" placeholder="value" class="col-span-6 {{ $cell }}">
                                    <button type="button" wire:click="removeVariable({{ $i }})" class="col-span-1 text-gray-400 hover:text-rose-500">✕</button>
                                </div>
                            @endforeach
                        </div>

                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input type="checkbox" wire:model="isActive" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                            Set as active environment
                        </label>

                        <div class="flex gap-2 pt-1">
                            <button wire:click="save" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                {{ $editingId ? 'Update' : 'Create' }}
                            </button>
                            @if ($editingId)
                                <button wire:click="newEnvironment" class="rounded-md px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">New</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
