@if (!empty($rows))
    <section>
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">{{ $title }}</h3>
        <div class="overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-left text-xs text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 font-medium">Name</th>
                        @if ($withType)<th class="px-3 py-2 font-medium">Type</th>@endif
                        <th class="px-3 py-2 font-medium">Required</th>
                        <th class="px-3 py-2 font-medium">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @foreach ($rows as $row)
                        <tr class="text-gray-700 dark:text-gray-300">
                            <td class="px-3 py-2 font-mono text-xs">{{ $row['name'] ?? '' }}</td>
                            @if ($withType)<td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $row['type'] ?? '—' }}</td>@endif
                            <td class="px-3 py-2">
                                @if (!empty($row['required']) && $row['required'] !== 'no')
                                    <span class="text-rose-600 dark:text-rose-400 text-xs font-medium">yes</span>
                                @else
                                    <span class="text-gray-400 text-xs">no</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $row['description'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
