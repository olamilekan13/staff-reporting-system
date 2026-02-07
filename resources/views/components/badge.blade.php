@props([
    'type' => 'info',
])

@php
    $typeClass = match ($type) {
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger' => 'badge-danger',
        'info', 'primary' => 'badge-primary',
        default => 'badge-primary',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$typeClass}"]) }}>
    {{ $slot }}
</span>
