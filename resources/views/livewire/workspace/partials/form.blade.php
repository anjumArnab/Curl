@php
    $input = 'mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $cell = 'rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<form wire:submit="saveEndpoint" class="max-w-3xl mx-auto p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ $mode === 'create' ? 'New Endpoint' : 'Edit Endpoint' }}
        </h2>
        <div class="flex gap-2">
            <button type="button" wire:click="cancelEdit"
                class="rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Cancel</button>
            <button type="submit"
                class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
        </div>
    </div>

    {{-- Basics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <input type="text" wire:model="form.name" class="{{ $input }}">
            @error('form.name') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
            <select wire:model="form.category_id" class="{{ $input }}">
                <option value="">— Uncategorised —</option>
                @foreach ($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Method</label>
            <select wire:model="form.method" class="{{ $input }}">
                @foreach ($methods as $method)
                    <option value="{{ $method->value }}">{{ $method->value }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL</label>
            <input type="text" wire:model="form.url" placeholder="/products/{id}" class="{{ $input }} font-mono">
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Relative paths use the active environment's <code>BASE_URL</code>. You can also paste a full URL.</p>
            @error('form.url') <span class="text-sm text-rose-600">{{ $message }}</span> @enderror
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description <span class="text-gray-400 font-normal">(Markdown)</span></label>
            <textarea wire:model="form.description" rows="3" class="{{ $input }}"></textarea>
        </div>
    </div>

    {{-- Authentication --}}
    <section class="space-y-3">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Authentication</h3>
        <select wire:model.live="form.auth_type" class="{{ $input }} sm:w-1/2">
            @foreach ($authTypes as $authType)
                <option value="{{ $authType->value }}">{{ $authType->label() }}</option>
            @endforeach
        </select>

        @if ($form['auth_type'] === 'bearer')
            <input type="text" wire:model="form.auth_config.token" placeholder="Token (supports @{{TOKEN}})" class="{{ $cell }} w-full">
        @elseif ($form['auth_type'] === 'basic')
            <div class="grid grid-cols-2 gap-2">
                <input type="text" wire:model="form.auth_config.username" placeholder="Username" class="{{ $cell }}">
                <input type="text" wire:model="form.auth_config.password" placeholder="Password" class="{{ $cell }}">
            </div>
        @elseif ($form['auth_type'] === 'api_key')
            <div class="grid grid-cols-3 gap-2">
                <input type="text" wire:model="form.auth_config.key" placeholder="Key name" class="{{ $cell }}">
                <input type="text" wire:model="form.auth_config.value" placeholder="Value" class="{{ $cell }}">
                <select wire:model="form.auth_config.in" class="{{ $cell }}">
                    <option value="header">In header</option>
                    <option value="query">In query</option>
                </select>
            </div>
        @elseif ($form['auth_type'] === 'custom')
            <div class="space-y-2">
                @foreach ($form['auth_config']['headers'] ?? [] as $i => $h)
                    <div wire:key="auth-h-{{ $i }}" class="grid grid-cols-12 gap-2">
                        <input type="text" wire:model="form.auth_config.headers.{{ $i }}.name" placeholder="Header" class="col-span-5 {{ $cell }}">
                        <input type="text" wire:model="form.auth_config.headers.{{ $i }}.value" placeholder="Value" class="col-span-6 {{ $cell }}">
                        <button type="button" wire:click="removeRow('authHeaders', {{ $i }})" class="col-span-1 text-gray-400 hover:text-rose-500">✕</button>
                    </div>
                @endforeach
                <button type="button" wire:click="addRow('authHeaders')" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">+ Add header</button>
            </div>
        @endif
    </section>

    @include('livewire.workspace.partials.form-params', ['field' => 'path_params', 'label' => 'Path Parameters'])
    @include('livewire.workspace.partials.form-params', ['field' => 'query_params', 'label' => 'Query Parameters'])
    @include('livewire.workspace.partials.form-params', ['field' => 'parameters', 'label' => 'Request Parameters'])

    {{-- Headers (no type column) --}}
    <section>
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Headers</h3>
            <button type="button" wire:click="addRow('headers')" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">+ Add</button>
        </div>
        @forelse ($form['headers'] as $i => $row)
            <div wire:key="header-{{ $i }}" class="grid grid-cols-12 gap-2 mb-2 items-center">
                <input type="text" wire:model="form.headers.{{ $i }}.name" placeholder="Header name" class="col-span-4 {{ $cell }}">
                <label class="col-span-3 flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400">
                    <input type="checkbox" wire:model="form.headers.{{ $i }}.required" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600"> required
                </label>
                <input type="text" wire:model="form.headers.{{ $i }}.description" placeholder="description" class="col-span-4 {{ $cell }}">
                <button type="button" wire:click="removeRow('headers', {{ $i }})" class="col-span-1 text-gray-400 hover:text-rose-500">✕</button>
            </div>
        @empty
            <p class="text-xs text-gray-400 italic mb-1">None</p>
        @endforelse
    </section>

    {{-- Request body --}}
    <section class="space-y-2">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Request Body</h3>
        <select wire:model.live="form.request_body.type" class="{{ $cell }} sm:w-1/3">
            <option value="none">None</option>
            <option value="json">JSON</option>
            <option value="raw">Raw</option>
            <option value="urlencoded">x-www-form-urlencoded</option>
            <option value="multipart">Multipart form-data</option>
        </select>
        @if (($form['request_body']['type'] ?? 'none') !== 'none')
            <textarea wire:model="form.request_body.content" rows="6" placeholder="Example body…"
                class="{{ $cell }} w-full font-mono text-xs"></textarea>
        @endif
    </section>

    {{-- Responses --}}
    <section>
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Responses</h3>
            <button type="button" wire:click="addRow('responses')" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">+ Add</button>
        </div>
        @forelse ($form['responses'] as $i => $row)
            <div wire:key="response-{{ $i }}" class="mb-3 rounded-md border border-gray-200 dark:border-gray-700 p-3 space-y-2">
                <div class="flex gap-2">
                    <input type="text" wire:model="form.responses.{{ $i }}.status" placeholder="200" class="w-24 {{ $cell }}">
                    <input type="text" wire:model="form.responses.{{ $i }}.description" placeholder="Description" class="flex-1 {{ $cell }}">
                    <button type="button" wire:click="removeRow('responses', {{ $i }})" class="text-gray-400 hover:text-rose-500">✕</button>
                </div>
                <textarea wire:model="form.responses.{{ $i }}.example" rows="4" placeholder="Example JSON payload…"
                    class="{{ $cell }} w-full font-mono text-xs"></textarea>
            </div>
        @empty
            <p class="text-xs text-gray-400 italic mb-1">None</p>
        @endforelse
    </section>

    <div class="flex justify-end gap-2 border-t border-gray-100 dark:border-gray-700 pt-4">
        <button type="button" wire:click="cancelEdit"
            class="rounded-md px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Cancel</button>
        <button type="submit"
            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save Endpoint</button>
    </div>
</form>
