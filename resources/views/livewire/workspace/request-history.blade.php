<div>
    <button wire:click="toggle"
        class="inline-flex items-center gap-1 rounded-md bg-gray-100 dark:bg-gray-700 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
        History
    </button>

    @if ($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="toggle"></div>
            <div class="relative w-full max-w-3xl rounded-lg bg-white dark:bg-gray-800 shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 px-5 py-3">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Request History</h2>
                    <div class="flex items-center gap-3">
                        @if ($histories->isNotEmpty())
                            <button wire:click="clear" wire:confirm="Clear all request history for this project?"
                                class="text-sm text-rose-600 dark:text-rose-400 hover:underline">Clear</button>
                        @endif
                        <button wire:click="toggle" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
                    </div>
                </div>

                <div class="max-h-[28rem] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($histories as $history)
                        <div wire:key="hist-{{ $history->id }}" class="flex items-center gap-3 px-5 py-2.5 text-sm">
                            @if ($history->error)
                                <span class="shrink-0 w-12 text-center text-[10px] font-bold rounded px-1 py-0.5 bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">ERR</span>
                            @else
                                <span class="shrink-0 w-12 text-center text-[10px] font-bold rounded px-1 py-0.5 {{ ($history->response_status ?? 0) < 400 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                    {{ $history->response_status ?? '—' }}
                                </span>
                            @endif
                            <span class="shrink-0 font-bold text-xs text-gray-500 dark:text-gray-400 w-14">{{ $history->method }}</span>
                            <span class="flex-1 min-w-0 truncate font-mono text-xs text-gray-600 dark:text-gray-300">{{ $history->url }}</span>
                            <span class="shrink-0 text-xs text-gray-400">{{ $history->response_time_ms }}ms</span>
                            <span class="shrink-0 text-xs text-gray-400">{{ $history->created_at->diffForHumans() }}</span>
                            <button wire:click="rerun({{ $history->id }})"
                                class="shrink-0 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Re-run</button>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-400 italic">No requests yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
