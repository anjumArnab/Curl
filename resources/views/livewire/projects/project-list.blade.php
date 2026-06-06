<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Projects</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Document, organise and test your APIs.</p>
            </div>
            @if ($canManage)
                <button wire:click="create"
                    class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <span class="text-lg leading-none">+</span> New Project
                </button>
            @endif
        </div>

        @if ($projects->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-12 text-center">
                <p class="text-gray-500 dark:text-gray-400">No projects yet.</p>
                @if ($canManage)
                    <button wire:click="create" class="mt-3 text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
                        Create your first project
                    </button>
                @endif
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    <div class="flex flex-col rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-5">
                        <div class="flex items-start justify-between gap-2">
                            <a href="{{ route('projects.workspace', $project) }}" wire:navigate
                                class="text-lg font-medium text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                {{ $project->name }}
                            </a>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $project->visibility === 'public'
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ ucfirst($project->visibility) }}
                            </span>
                        </div>

                        @if ($project->description)
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ $project->description }}</p>
                        @endif

                        <div class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                            {{ $project->endpoints_count }} endpoints · {{ $project->categories_count }} categories
                        </div>

                        <div class="mt-4 flex items-center gap-3 border-t border-gray-100 dark:border-gray-700 pt-3">
                            <a href="{{ route('projects.workspace', $project) }}" wire:navigate
                                class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Open</a>
                            @if ($canManage)
                                <button wire:click="edit({{ $project->id }})"
                                    class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Edit</button>
                                <button wire:click="delete({{ $project->id }})"
                                    wire:confirm="Delete “{{ $project->name }}” and all its endpoints?"
                                    class="text-sm text-rose-600 dark:text-rose-400 hover:underline ml-auto">Delete</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Create / edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>
            <div class="relative w-full max-w-lg rounded-lg bg-white dark:bg-gray-800 shadow-xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    {{ $editing ? 'Edit Project' : 'New Project' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                        <input type="text" wire:model="form.name"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('form.name') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea wire:model="form.description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        @error('form.description') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Visibility</label>
                        <select wire:model="form.visibility"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="private">Private</option>
                            <option value="public">Public</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="rounded-md px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Cancel</button>
                        <button type="submit"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            {{ $editing ? 'Save' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
