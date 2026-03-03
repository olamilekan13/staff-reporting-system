@extends('layouts.app')

@section('title', 'Manage Videos')
@section('page-title', 'Manage Videos')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Manage video content and on-demand training</p>
        </div>
        <div class="flex items-center gap-3">
            <x-button variant="outline" size="sm" :href="route('admin.video-categories.index')">
                Categories
            </x-button>
            @can('create', App\Models\Video::class)
                <x-button variant="primary" :href="route('admin.videos.create')">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Video
                </x-button>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <div x-data="{ showFilters: {{ request()->hasAny(['status', 'category_id', 'search']) ? 'true' : 'false' }} }" class="mb-6">
        <button @click="showFilters = !showFilters" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1 mb-3">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
            </svg>
            <span x-text="showFilters ? 'Hide Filters' : 'Show Filters'"></span>
        </button>

        <div x-show="showFilters" x-transition class="card px-6 py-4">
            <form method="GET" action="{{ route('admin.videos.index') }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <x-input name="search" label="Search" placeholder="Search by title..." :value="request('search')" />

                    <x-select name="status" label="Status" placeholder="All Statuses" :selected="request('status')"
                        :options="['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']" />

                    <x-select name="category_id" label="Category" placeholder="All Categories" :selected="request('category_id')"
                        :options="$categories->pluck('name', 'id')->toArray()" />
                </div>

                <div class="flex items-center gap-3 mt-4">
                    <x-button type="submit" variant="primary" size="sm">Filter</x-button>
                    <a href="{{ route('admin.videos.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.livestream-attendance') }}" class="text-sm text-primary-600 hover:text-primary-800 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
            </svg>
            Live Stream Attendance
        </a>
    </div>

    {{-- Videos table --}}
    @if($videos->isEmpty())
        <x-card>
            <x-empty-state title="No videos found" description="No videos match your current filters.">
                <x-slot:action>
                    @can('create', App\Models\Video::class)
                        <x-button variant="primary" size="sm" :href="route('admin.videos.create')">Add Video</x-button>
                    @endcan
                </x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <x-data-table :headers="['title' => 'Title', 'category' => 'Category', 'source' => 'Source', 'target' => 'Target', 'status' => 'Status', 'views' => 'Views', 'date' => 'Date', 'actions' => '']">
            @foreach($videos as $video)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            @if($video->getThumbnailUrl())
                                <img src="{{ $video->getThumbnailUrl() }}" alt="" class="w-12 h-8 rounded object-cover shrink-0">
                            @else
                                <div class="w-12 h-8 rounded bg-gray-100 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <a href="{{ route('admin.videos.show', $video) }}" class="font-medium text-gray-900 hover:text-primary-600">
                                    {{ Str::limit($video->title, 40) }}
                                </a>
                                @if($video->duration_seconds)
                                    <p class="text-xs text-gray-400">{{ $video->getFormattedDuration() }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">
                        {{ $video->category?->name ?? '-' }}
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        @php $badge = $video->getSourceTypeBadge(); @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badge['class'] }}">
                            {{ $badge['label'] }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">
                        @if($video->target_type === 'all')
                            All Users
                        @elseif($video->target_type === 'departments')
                            {{ $video->departments->count() }} {{ Str::plural('Dept', $video->departments->count()) }}
                        @elseif($video->target_type === 'users')
                            {{ $video->users->count() }} {{ Str::plural('User', $video->users->count()) }}
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        @php $statusBadge = $video->getStatusBadge(); @endphp
                        <x-badge :type="$statusBadge['type']">{{ $statusBadge['label'] }}</x-badge>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">
                        {{ $video->watchLogs()->count() }}
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500 whitespace-nowrap">
                        {{ $video->created_at->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-3">
                        <x-dropdown>
                            <x-slot:trigger>
                                <button class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                    </svg>
                                </button>
                            </x-slot:trigger>

                            <a href="{{ route('admin.videos.show', $video) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">View</a>
                            <a href="{{ route('admin.videos.attendance', $video) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Attendance</a>
                            @can('update', $video)
                                <a href="{{ route('admin.videos.edit', $video) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit</a>
                            @endcan
                            @can('delete', $video)
                                <form method="POST" action="{{ route('admin.videos.destroy', $video) }}" onsubmit="return confirm('Are you sure you want to delete this video?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Delete</button>
                                </form>
                            @endcan
                        </x-dropdown>
                    </td>
                </tr>
            @endforeach

            <x-slot:pagination>
                {{ $videos->withQueryString()->links() }}
            </x-slot:pagination>
        </x-data-table>
    @endif
@endsection
