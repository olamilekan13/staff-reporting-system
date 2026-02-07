@props([
    'label' => null,
    'name',
    'type' => 'text',
    'required' => false,
    'error' => null,
    'placeholder' => '',
])

<div>
    @if($label)
        <label for="{{ $name }}" class="label">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'input' . ($error ? ' border-red-300 focus:ring-red-500 focus:border-red-500' : '')]) }}
    >

    @if($error)
        <p class="mt-1.5 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
