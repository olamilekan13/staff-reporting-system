@props([
    'title',
    'url',
    'type' => 'report', // report, proposal, comment, announcement
    'size' => 'sm',
    'variant' => 'primary'
])

<button
    type="button"
    x-data
    @click="$dispatch('kingschat-share', { title: '{{ addslashes($title) }}', url: '{{ $url }}', type: '{{ $type }}' })"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-1.5 border border-transparent font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed ' . match($size) {
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-4 py-2 text-base',
        default => 'px-3 py-2 text-sm'
    } . ' ' . match($variant) {
        'primary' => 'text-white bg-primary-600 hover:bg-primary-700 focus:ring-primary-500',
        'secondary' => 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-primary-500',
        'success' => 'text-white bg-green-600 hover:bg-green-700 focus:ring-green-500',
        default => 'text-white bg-primary-600 hover:bg-primary-700 focus:ring-primary-500'
    }]) }}
>
    {{-- Share Icon SVG --}}
    <svg class="{{ $size === 'sm' ? 'w-4 h-4' : 'w-5 h-5' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
    </svg>

    {{ $slot->isEmpty() ? 'Share' : $slot }}
</button>
