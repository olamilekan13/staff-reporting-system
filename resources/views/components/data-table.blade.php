@props([
    'headers' => [],
    'sortable' => false,
    'sortBy' => null,
    'sortDir' => 'asc',
])

<div {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/50">
                    @foreach($headers as $key => $label)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            @if($sortable)
                                <a href="?sort={{ $key }}&direction={{ $sortBy === $key && $sortDir === 'asc' ? 'desc' : 'asc' }}"
                                   class="flex items-center gap-1 hover:text-gray-700">
                                    {{ $label }}
                                    @if($sortBy === $key)
                                        <svg class="w-3 h-3 {{ $sortDir === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                                        </svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                        </svg>
                                    @endif
                                </a>
                            @else
                                {{ $label }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($pagination)
        <div class="px-6 py-3 border-t border-gray-100">
            {{ $pagination }}
        </div>
    @endisset
</div>
