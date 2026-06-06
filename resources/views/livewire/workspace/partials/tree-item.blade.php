<button wire:click="selectEndpoint({{ $endpoint->id }})" wire:key="ep-{{ $endpoint->id }}"
    class="w-full flex items-center gap-2 rounded px-2 py-1 text-left text-sm
    {{ $selectedEndpointId === $endpoint->id
        ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
    <span class="shrink-0 w-14 text-center text-[10px] font-bold rounded px-1 py-0.5 {{ $endpoint->method->badgeClasses() }}">
        {{ $endpoint->method->value }}
    </span>
    <span class="truncate">{{ $endpoint->name }}</span>
</button>
