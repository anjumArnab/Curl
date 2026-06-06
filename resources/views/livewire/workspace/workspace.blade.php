<div class="h-[calc(100vh-4rem)] flex flex-col bg-gray-50 dark:bg-gray-900">
    {{-- Top bar --}}
    <div class="flex items-center justify-between gap-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('projects.index') }}" wire:navigate
                class="inline-flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Back to projects">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="truncate text-base font-semibold text-gray-900 dark:text-gray-100">{{ $project->name }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <livewire:workspace.environment-manager :project="$project" />
            <livewire:workspace.request-history :project="$project" />
        </div>
    </div>

    <div class="flex-1 flex overflow-hidden">
        {{-- ============ LEFT: API tree ============ --}}
        <aside class="w-72 shrink-0 flex flex-col border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="Search endpoints…"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="flex-1 overflow-y-auto p-2 space-y-1">
                @php $uncategorized = $this->endpoints->whereNull('category_id'); @endphp

                @foreach ($this->categories as $category)
                    @php $items = $this->endpoints->where('category_id', $category->id); @endphp
                    <div x-data="{ open: true }">
                        <div class="group flex items-center gap-1 rounded px-1.5 py-1 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <button @click="open = !open" class="text-gray-400 w-4 text-xs">
                                <span x-show="open">▾</span><span x-show="!open">▸</span>
                            </button>
                            <span class="flex-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 truncate">{{ $category->name }}</span>
                            <button wire:click="newEndpoint({{ $category->id }})" title="New endpoint in {{ $category->name }}"
                                class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-indigo-500 text-sm">+</button>
                            <button wire:click="deleteCategory({{ $category->id }})" wire:confirm="Delete category “{{ $category->name }}”? Its endpoints move to Uncategorised."
                                class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-rose-500 text-xs">✕</button>
                        </div>
                        <div x-show="open" class="ml-3 mt-0.5 space-y-0.5">
                            @forelse ($items as $endpoint)
                                @include('livewire.workspace.partials.tree-item', ['endpoint' => $endpoint])
                            @empty
                                <p class="px-2 py-1 text-xs text-gray-400 italic">No endpoints</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                {{-- Uncategorised --}}
                @if ($uncategorized->isNotEmpty())
                    <div x-data="{ open: true }" class="pt-1">
                        <div class="flex items-center gap-1 px-1.5 py-1">
                            <button @click="open = !open" class="text-gray-400 w-4 text-xs">
                                <span x-show="open">▾</span><span x-show="!open">▸</span>
                            </button>
                            <span class="flex-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Uncategorised</span>
                        </div>
                        <div x-show="open" class="ml-3 mt-0.5 space-y-0.5">
                            @foreach ($uncategorized as $endpoint)
                                @include('livewire.workspace.partials.tree-item', ['endpoint' => $endpoint])
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Add category + new endpoint --}}
            <div class="border-t border-gray-100 dark:border-gray-700 p-2 space-y-2">
                <form wire:submit="addCategory" class="flex gap-1">
                    <input type="text" wire:model="newCategoryName" placeholder="New category"
                        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="submit" class="rounded-md bg-gray-100 dark:bg-gray-700 px-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">+</button>
                </form>
                <button wire:click="newEndpoint"
                    class="w-full rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                    + New Endpoint
                </button>
            </div>
        </aside>

        {{-- ============ CENTER: documentation / form ============ --}}
        <main class="flex-1 overflow-y-auto">
            @if ($mode === 'view' && $this->selectedEndpoint)
                @include('livewire.workspace.partials.documentation', ['endpoint' => $this->selectedEndpoint])
            @elseif (in_array($mode, ['edit', 'create']))
                @include('livewire.workspace.partials.form')
            @else
                <div class="h-full flex items-center justify-center text-center p-8">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Select an endpoint to view its documentation,</p>
                        <button wire:click="newEndpoint" class="mt-2 text-indigo-600 dark:text-indigo-400 font-medium hover:underline">or create a new one</button>
                    </div>
                </div>
            @endif
        </main>

        {{-- ============ RIGHT: tester ============ --}}
        <aside class="w-96 shrink-0 overflow-y-auto border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <livewire:workspace.request-tester :project="$project" :endpoint-id="$selectedEndpointId"
                :key="'tester-'.($selectedEndpointId ?? 'none')" />
        </aside>
    </div>
</div>
