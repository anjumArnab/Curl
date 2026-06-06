@php use App\Enums\AuthType; @endphp
<div class="max-w-3xl mx-auto p-6 space-y-8">
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $endpoint->name }}</h2>
            <div class="mt-2 flex items-center gap-2">
                <span class="text-xs font-bold rounded px-2 py-1 {{ $endpoint->method->badgeClasses() }}">{{ $endpoint->method->value }}</span>
                <code class="text-sm text-gray-600 dark:text-gray-300 break-all bg-gray-100 dark:bg-gray-700/60 rounded px-2 py-1">{{ $endpoint->url }}</code>
            </div>
        </div>
        <div class="flex shrink-0 gap-2">
            <button wire:click="editEndpoint({{ $endpoint->id }})"
                class="rounded-md bg-gray-100 dark:bg-gray-700 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">Edit</button>
            <button wire:click="deleteEndpoint({{ $endpoint->id }})" wire:confirm="Delete this endpoint?"
                class="rounded-md px-3 py-1.5 text-sm font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30">Delete</button>
        </div>
    </div>

    {{-- Auth --}}
    <div class="text-sm">
        <span class="font-medium text-gray-700 dark:text-gray-300">Authentication:</span>
        <span class="text-gray-600 dark:text-gray-400">{{ (AuthType::tryFrom($endpoint->auth_type->value) ?? AuthType::None)->label() }}</span>
    </div>

    {{-- Description (markdown) --}}
    @if ($endpoint->description)
        <section>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Description</h3>
            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-2 [&_code]:bg-gray-100 dark:[&_code]:bg-gray-700 [&_code]:px-1 [&_code]:rounded [&_a]:text-indigo-600 [&_a]:underline">
                {!! \Illuminate\Support\Str::markdown($endpoint->description, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
            </div>
        </section>
    @endif

    @include('livewire.workspace.partials.param-table', [
        'title' => 'Path Parameters', 'rows' => $endpoint->path_params, 'withType' => true,
    ])
    @include('livewire.workspace.partials.param-table', [
        'title' => 'Query Parameters', 'rows' => $endpoint->query_params, 'withType' => true,
    ])
    @include('livewire.workspace.partials.param-table', [
        'title' => 'Request Parameters', 'rows' => $endpoint->parameters, 'withType' => true,
    ])
    @include('livewire.workspace.partials.param-table', [
        'title' => 'Headers', 'rows' => $endpoint->headers, 'withType' => false,
    ])

    {{-- Request body --}}
    @php $body = $endpoint->request_body ?? []; @endphp
    @if (!empty($body['content']))
        <section>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
                Request Body <span class="text-gray-400 normal-case font-normal">({{ $body['type'] ?? 'raw' }})</span>
            </h3>
            <pre class="text-xs rounded-md bg-gray-900 text-gray-100 p-3 overflow-x-auto">{{ $body['content'] }}</pre>
        </section>
    @endif

    {{-- Responses --}}
    @if (!empty($endpoint->responses))
        <section>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">Responses</h3>
            <div class="space-y-3">
                @foreach ($endpoint->responses as $response)
                    <div class="rounded-md border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-xs font-bold rounded px-2 py-0.5
                                {{ (int) ($response['status'] ?? 0) < 300 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                {{ $response['status'] ?? '—' }}
                            </span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $response['description'] ?? '' }}</span>
                        </div>
                        @if (!empty($response['example']))
                            <pre class="text-xs rounded-b-md bg-gray-900 text-gray-100 p-3 overflow-x-auto">{{ $response['example'] }}</pre>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
