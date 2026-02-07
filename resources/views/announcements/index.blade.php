@extends('layouts.app')

@section('title', 'Announcements')
@section('page-title', 'Announcements')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Stay updated with the latest announcements</p>
        </div>
        @can('create', App\Models\Announcement::class)
            <x-button variant="secondary" size="sm" :href="route('admin.announcements.index')">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.204-.107-.397.165-.71.505-.78.929l-.15.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                Manage
            </x-button>
        @endcan
    </div>

    {{-- Filter --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('announcements.index') }}" class="flex items-center gap-3">
            <div class="w-48">
                <x-select name="priority" placeholder="All Priorities" :selected="request('priority')"
                    :options="['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent']" />
            </div>
            <x-button type="submit" variant="secondary" size="sm">Filter</x-button>
            @if(request('priority'))
                <a href="{{ route('announcements.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
    </div>

    @if($announcements->isEmpty())
        <x-card>
            <x-empty-state title="No announcements" description="There are no announcements to display at this time." />
        </x-card>
    @else
        <div class="space-y-4">
            @foreach($announcements as $announcement)
                <a href="{{ route('announcements.show', $announcement) }}"
                   class="block card hover:shadow-md transition-shadow duration-200 {{ $announcement->is_pinned ? 'border-l-4 border-l-primary-500' : '' }}">
                    <div class="px-6 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    @if($announcement->is_pinned)
                                        <svg class="w-4 h-4 text-primary-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/>
                                        </svg>
                                    @endif
                                    <h3 class="text-base font-semibold text-gray-900 truncate {{ !$announcement->isReadBy($user) ? '' : 'text-gray-600' }}">
                                        {{ $announcement->title }}
                                    </h3>
                                </div>

                                <p class="text-sm text-gray-500 line-clamp-2 mb-3">
                                    {{ Str::limit(strip_tags($announcement->content), 150) }}
                                </p>

                                <div class="flex items-center gap-3 text-xs text-gray-400">
                                    <span>{{ $announcement->creator?->full_name }}</span>
                                    <span>&middot;</span>
                                    <span>{{ $announcement->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if(!$announcement->isReadBy($user))
                                    <span class="w-2.5 h-2.5 bg-primary-500 rounded-full" title="Unread"></span>
                                @endif
                                <span class="badge {{ $announcement->getPriorityBadgeClass() }}">
                                    {{ ucfirst($announcement->priority) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $announcements->withQueryString()->links() }}
        </div>
    @endif
@endsection
