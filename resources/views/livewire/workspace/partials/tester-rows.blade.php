@php
    $cell = 'rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $rows = $this->{$field};
@endphp
<section>
    <div class="flex items-center justify-between mb-1">
        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</span>
        <button type="button" wire:click="addRow('{{ $field }}')" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">+ Add</button>
    </div>
    @forelse ($rows as $i => $row)
        <div wire:key="{{ $field }}-{{ $i }}" class="grid grid-cols-12 gap-1 mb-1 items-center">
            <input type="text" wire:model="{{ $field }}.{{ $i }}.name" placeholder="name" class="col-span-5 {{ $cell }}">
            <input type="text" wire:model="{{ $field }}.{{ $i }}.value" placeholder="value" class="col-span-6 {{ $cell }}">
            <button type="button" wire:click="removeRow('{{ $field }}', {{ $i }})" class="col-span-1 text-gray-400 hover:text-rose-500">✕</button>
        </div>
    @empty
        <p class="text-[11px] text-gray-400 italic">None</p>
    @endforelse
</section>
