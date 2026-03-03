@extends('layouts.app')

@section('title', 'Videos')
@section('page-title', 'Videos')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="mb-6">
        <p class="text-sm text-gray-500">Browse and watch available videos</p>
    </div>

    {{-- Category filter + search --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-6">
        <form method="GET" action="{{ route('videos.index') }}" class="flex flex-wrap items-center gap-3 w-full">
            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <a href="{{ route('videos.index', array_filter(['search' => request('search')])) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium transition-colors
                          {{ !request('category_id') ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    All
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('videos.index', array_filter(['category_id' => $cat->id, 'search' => request('search')])) }}"
                       class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors
                              {{ request('category_id') == $cat->id ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
            <div class="flex items-center gap-2 ml-auto">
                <input type="text" name="search" placeholder="Search videos..." class="input py-1.5 text-sm w-56" value="{{ request('search') }}">
                @if(request('category_id'))
                    <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                @endif
                <x-button type="submit" variant="primary" size="sm">Search</x-button>
            </div>
        </form>
    </div>

    {{-- Video grid --}}
    @if($videos->isEmpty())
        <x-card>
            <x-empty-state title="No videos available" description="There are no videos available for you at this time. Check back later." />
        </x-card>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($videos as $video)
                <a href="{{ route('videos.show', $video) }}" class="group block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                    {{-- Thumbnail --}}
                    <div class="relative aspect-video bg-gray-100">
                        @if($video->getThumbnailUrl())
                            <img src="{{ $video->getThumbnailUrl() }}" alt="{{ $video->title }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                <svg class="w-10 h-10 text-gray-500 group-hover:text-primary-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                </svg>
                            </div>
                        @endif
                        @if($video->duration_seconds)
                            <span class="absolute bottom-2 right-2 px-1.5 py-0.5 bg-black/75 text-white text-xs rounded">
                                {{ $video->getFormattedDuration() }}
                            </span>
                        @endif
                        @php $badge = $video->getSourceTypeBadge(); @endphp
                        <span class="absolute top-2 left-2 px-1.5 py-0.5 rounded text-xs font-medium {{ $badge['class'] }}">
                            {{ $badge['label'] }}
                        </span>
                    </div>
                    {{-- Info --}}
                    <div class="p-3">
                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-primary-600 line-clamp-2 transition-colors">
                            {{ $video->title }}
                        </h3>
                        <div class="flex items-center gap-2 mt-1.5">
                            @if($video->category)
                                <span class="text-xs text-primary-600 font-medium">{{ $video->category->name }}</span>
                                <span class="text-gray-300">&middot;</span>
                            @endif
                            <span class="text-xs text-gray-400">{{ $video->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $videos->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
