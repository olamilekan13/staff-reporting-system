@props([
    'title',
    'value',
    'trend' => null,
    'trendUp' => null,
])

<div {{ $attributes->merge(['class' => 'card px-6 py-4']) }}>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $value }}</p>

            @if($trend)
                <p class="text-sm mt-1 flex items-center gap-1
                    {{ $trendUp === true ? 'text-green-600' : ($trendUp === false ? 'text-red-600' : 'text-gray-500') }}">
                    @if($trendUp === true)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
                        </svg>
                    @elseif($trendUp === false)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.986l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181" />
                        </svg>
                    @endif
                    {{ $trend }}
                </p>
            @endif
        </div>

        @isset($icon)
            <div class="shrink-0 w-10 h-10 rounded-lg bg-primary-50 flex items-center justify-center text-primary-600">
                {{ $icon }}
            </div>
        @endisset
    </div>
</div>
