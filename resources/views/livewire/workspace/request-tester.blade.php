@php
    $cell = 'rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $tabBtn = 'shrink-0 px-2.5 py-1.5 rounded-md text-[11px] font-medium transition flex items-center gap-1';
@endphp
<div
    x-data="{ reqTab: 'headers', expanded: false }"
    @keydown.escape.window="expanded = false"
    :class="expanded ? 'fixed inset-0 z-40 flex flex-col bg-white dark:bg-gray-800 shadow-2xl' : 'flex flex-col h-full'"
>
    {{-- Header --}}
    <div class="flex items-center justify-between p-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tester</h2>
        <button type="button" @click="expanded = !expanded"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
            :title="expanded ? 'Collapse (Esc)' : 'Expand'">
            <svg x-show="!expanded" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M20.25 20.25v-4.5m0 4.5h-4.5m4.5 0L15 15M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75v4.5m0-4.5h-4.5m4.5 0L15 9" />
            </svg>
            <svg x-show="expanded" x-cloak class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" />
            </svg>
        </button>
    </div>

    {{-- Body: request + response. Row when expanded, column when docked. --}}
    <div :class="expanded ? 'flex-1 flex flex-row overflow-hidden' : 'flex-1 flex flex-col overflow-hidden'">

        {{-- ===== Request panel ===== --}}
        <div :class="expanded ? 'w-1/2 flex flex-col border-r border-gray-200 dark:border-gray-700 overflow-y-auto' : 'flex flex-col overflow-y-auto'"
            class="p-3 space-y-3">
            {{-- Environment --}}
            <div>
                <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">Environment</label>
                <select wire:model.live="environmentId" class="{{ $cell }} w-full">
                    <option value="">No environment</option>
                    @foreach ($environments as $environment)
                        <option value="{{ $environment->id }}">{{ $environment->name }}{{ $environment->is_active ? ' (active)' : '' }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Method + URL + Send (one row) --}}
            <div class="flex gap-1">
                <select wire:model="method" class="{{ $cell }} w-24 font-semibold">
                    @foreach ($methods as $m)<option value="{{ $m->value }}">{{ $m->value }}</option>@endforeach
                </select>
                <input type="text" wire:model="url" placeholder="/path" class="{{ $cell }} flex-1 font-mono">
                <button wire:click="send" wire:loading.attr="disabled" wire:target="send"
                    class="shrink-0 rounded-md bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60">
                    <span wire:loading.remove wire:target="send">Send</span>
                    <span wire:loading wire:target="send">…</span>
                </button>
            </div>
            @error('url') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

            {{-- Tab bar --}}
            @php
                $activeCls = 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300';
                $idleCls = 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700';
                $countCls = 'rounded-full bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-200 text-[9px] leading-none px-1.5 py-0.5';
                $dotCls = 'h-1.5 w-1.5 rounded-full bg-indigo-500';
            @endphp
            <div class="flex gap-1 overflow-x-auto border-y border-gray-100 dark:border-gray-700 py-1.5">
                <button type="button" @click="reqTab = 'headers'" class="{{ $tabBtn }}" :class="reqTab === 'headers' ? '{{ $activeCls }}' : '{{ $idleCls }}'">
                    Headers @if (count($headerRows))<span class="{{ $countCls }}">{{ count($headerRows) }}</span>@endif
                </button>
                <button type="button" @click="reqTab = 'query'" class="{{ $tabBtn }}" :class="reqTab === 'query' ? '{{ $activeCls }}' : '{{ $idleCls }}'">
                    Query @if (count($queryRows))<span class="{{ $countCls }}">{{ count($queryRows) }}</span>@endif
                </button>
                <button type="button" @click="reqTab = 'path'" class="{{ $tabBtn }}" :class="reqTab === 'path' ? '{{ $activeCls }}' : '{{ $idleCls }}'">
                    Path @if (count($pathRows))<span class="{{ $countCls }}">{{ count($pathRows) }}</span>@endif
                </button>
                <button type="button" @click="reqTab = 'auth'" class="{{ $tabBtn }}" :class="reqTab === 'auth' ? '{{ $activeCls }}' : '{{ $idleCls }}'">
                    Auth @if ($authType !== 'none')<span class="{{ $dotCls }}"></span>@endif
                </button>
                <button type="button" @click="reqTab = 'body'" class="{{ $tabBtn }}" :class="reqTab === 'body' ? '{{ $activeCls }}' : '{{ $idleCls }}'">
                    Body @if ($bodyType !== 'none')<span class="{{ $dotCls }}"></span>@endif
                </button>
            </div>

            {{-- Tab panels --}}
            <div class="flex-1">
                <div x-show="reqTab === 'headers'">
                    @include('livewire.workspace.partials.tester-rows', ['field' => 'headerRows', 'label' => 'Headers'])
                </div>
                <div x-show="reqTab === 'query'" x-cloak>
                    @include('livewire.workspace.partials.tester-rows', ['field' => 'queryRows', 'label' => 'Query Params'])
                </div>
                <div x-show="reqTab === 'path'" x-cloak>
                    @include('livewire.workspace.partials.tester-rows', ['field' => 'pathRows', 'label' => 'Path Params'])
                </div>

                {{-- Auth --}}
                <div x-show="reqTab === 'auth'" x-cloak class="space-y-2">
                    <select wire:model.live="authType" class="{{ $cell }} w-full">
                        @foreach ($authTypes as $authType)<option value="{{ $authType->value }}">{{ $authType->label() }}</option>@endforeach
                    </select>
                    @if ($authType === 'bearer')
                        <input type="text" wire:model="authConfig.token" placeholder="Token" class="{{ $cell }} w-full">
                    @elseif ($authType === 'basic')
                        <div class="grid grid-cols-2 gap-1">
                            <input type="text" wire:model="authConfig.username" placeholder="Username" class="{{ $cell }}">
                            <input type="text" wire:model="authConfig.password" placeholder="Password" class="{{ $cell }}">
                        </div>
                    @elseif ($authType === 'api_key')
                        <div class="grid grid-cols-3 gap-1">
                            <input type="text" wire:model="authConfig.key" placeholder="Key" class="{{ $cell }}">
                            <input type="text" wire:model="authConfig.value" placeholder="Value" class="{{ $cell }}">
                            <select wire:model="authConfig.in" class="{{ $cell }}">
                                <option value="header">header</option>
                                <option value="query">query</option>
                            </select>
                        </div>
                    @endif
                </div>

                {{-- Body --}}
                <div x-show="reqTab === 'body'" x-cloak class="space-y-2">
                    <select wire:model.live="bodyType" class="{{ $cell }} w-full">
                        <option value="none">None</option>
                        <option value="json">JSON</option>
                        <option value="raw">Raw</option>
                        <option value="urlencoded">x-www-form-urlencoded</option>
                        <option value="multipart">Multipart form-data</option>
                    </select>

                    @if (in_array($bodyType, ['json', 'raw']))
                        <textarea wire:model="body" rows="8" class="{{ $cell }} w-full font-mono" placeholder="Request body…"></textarea>
                    @elseif (in_array($bodyType, ['urlencoded', 'multipart']))
                        @include('livewire.workspace.partials.tester-rows', ['field' => 'formRows', 'label' => 'Fields'])
                        @if ($bodyType === 'multipart')
                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">Files</span>
                                    <button type="button" wire:click="addRow('files')" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">+ Add</button>
                                </div>
                                @foreach ($files as $i => $row)
                                    <div wire:key="file-{{ $i }}" class="grid grid-cols-12 gap-1 items-center">
                                        <input type="text" wire:model="files.{{ $i }}.name" placeholder="field" class="col-span-4 {{ $cell }}">
                                        <input type="file" wire:model="files.{{ $i }}.upload" class="col-span-7 text-xs text-gray-500 dark:text-gray-400">
                                        <button type="button" wire:click="removeRow('files', {{ $i }})" class="col-span-1 text-gray-400 hover:text-rose-500">✕</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== Response panel ===== --}}
        <div
            :class="expanded ? 'w-1/2 flex flex-col overflow-y-auto' : 'border-t border-gray-200 dark:border-gray-700 max-h-[45%] overflow-y-auto shrink-0'"
            class="bg-gray-50 dark:bg-gray-900/60">
            @if ($response !== null)
                @php
                    $content = $response['pretty'] ?? $response['body'] ?? '';
                    $isJson = ($response['pretty'] ?? null) !== null;
                    $sig = md5(($response['body'] ?? '').($response['time_ms'] ?? ''));
                @endphp
                <div x-data="{ tab: 'body' }">
                    <div class="flex items-center gap-3 px-3 py-2 text-xs sticky top-0 bg-gray-50 dark:bg-gray-900/80 backdrop-blur">
                        @if ($response['error'])
                            <span class="font-bold text-rose-600 dark:text-rose-400">Error</span>
                        @else
                            <span class="font-bold rounded px-2 py-0.5 {{ ($response['status'] ?? 0) < 400 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                {{ $response['status'] }}
                            </span>
                        @endif
                        <span class="text-gray-500 dark:text-gray-400">{{ $response['time_ms'] }} ms</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ number_format($response['size'] / 1024, 1) }} KB</span>
                        <div class="ml-auto flex gap-2">
                            <button @click="tab = 'body'" :class="tab === 'body' ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-400'">Body</button>
                            <button @click="tab = 'headers'" :class="tab === 'headers' ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-400'">Headers</button>
                        </div>
                    </div>

                    @if ($response['error'])
                        <pre class="text-xs text-rose-600 dark:text-rose-400 px-3 pb-3 whitespace-pre-wrap">{{ $response['error'] }}</pre>
                    @else
                        <div x-show="tab === 'body'" x-data="responseViewer(@js($content), @js($isJson))" wire:key="resp-{{ $sig }}">
                            {{-- Search bar --}}
                            <div class="flex items-center gap-1 px-3 pb-2">
                                <input type="text" x-model="query" @input.debounce.250ms="search()" @keydown.enter.prevent="find(1)"
                                    placeholder="Search response…" class="{{ $cell }} flex-1">
                                <span x-show="query" x-cloak class="text-[11px] tabular-nums text-gray-400 w-10 text-center"
                                    x-text="matches.length ? (current + 1) + '/' + matches.length : '0/0'"></span>
                                <button type="button" @click="find(-1)" title="Previous match"
                                    class="inline-flex items-center justify-center px-2 py-1.5 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <svg class="h-4 w-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                                <button type="button" @click="find(1)" title="Next match"
                                    class="inline-flex items-center justify-center px-2 py-1.5 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </div>
                            <div x-ref="editor" wire:ignore class="px-1 pb-3"></div>
                        </div>
                        <div x-show="tab === 'headers'" x-cloak>
                            <div class="px-3 pb-3 text-xs space-y-0.5">
                                @foreach ($response['headers'] as $name => $value)
                                    <div class="flex gap-2"><span class="font-medium text-gray-600 dark:text-gray-300">{{ $name }}:</span><span class="text-gray-500 dark:text-gray-400 break-all">{{ $value }}</span></div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="h-full flex items-center justify-center p-6 text-center">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Send a request to see the response.</p>
                </div>
            @endif
        </div>
    </div>
</div>
