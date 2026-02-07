@props(['href' => '#', 'active' => false])

<a href="{{ $href }}"
   class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-150
          {{ $active
              ? 'bg-primary-50 text-primary-700'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
    {{-- Icon slot --}}
    @if(isset($icon))
        <span class="w-5 h-5 shrink-0">{{ $icon }}</span>
    @endif

    <span>{{ $slot }}</span>
</a>
