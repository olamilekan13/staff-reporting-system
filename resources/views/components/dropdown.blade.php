@props([
    'align' => 'right',
    'width' => '48',
])

@php
    $alignClass = match ($align) {
        'left' => 'left-0',
        'right' => 'right-0',
        default => 'right-0',
    };

    $widthClass = "w-{$width}";
@endphp

<div x-data="{ open: false }" class="relative" {{ $attributes }}>
    {{-- Trigger --}}
    <div @click="open = !open">
        {{ $trigger }}
    </div>

    {{-- Content --}}
    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute {{ $alignClass }} mt-2 {{ $widthClass }} bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
        style="display: none;"
    >
        {{ $slot }}
    </div>
</div>
