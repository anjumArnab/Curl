@php
    $input = 'rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $types = ['string', 'integer', 'float', 'boolean', 'array', 'object', 'date', 'datetime', 'file'];
@endphp
<section>
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</h3>
        <button type="button" wire:click="addRow('{{ $field }}')"
            class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">+ Add</button>
    </div>

    @forelse ($form[$field] as $i => $row)
        <div wire:key="{{ $field }}-{{ $i }}" class="grid grid-cols-12 gap-2 mb-2 items-center">
            <input type="text" wire:model="form.{{ $field }}.{{ $i }}.name" placeholder="name" class="col-span-3 {{ $input }}">
            <select wire:model="form.{{ $field }}.{{ $i }}.type" class="col-span-2 {{ $input }}">
                @foreach ($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
            </select>
            <label class="col-span-2 flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400">
                <input type="checkbox" wire:model="form.{{ $field }}.{{ $i }}.required" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                required
            </label>
            <input type="text" wire:model="form.{{ $field }}.{{ $i }}.description" placeholder="description" class="col-span-4 {{ $input }}">
            <button type="button" wire:click="removeRow('{{ $field }}', {{ $i }})" class="col-span-1 text-gray-400 hover:text-rose-500">✕</button>
        </div>
    @empty
        <p class="text-xs text-gray-400 italic mb-1">None</p>
    @endforelse
</section>
