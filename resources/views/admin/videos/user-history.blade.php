@extends('layouts.app')

@section('title', $user->full_name . ' - Watch History')
@section('page-title', 'Watch History')

@section('content')
    <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to {{ $user->full_name }}
    </a>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ $user->full_name }}</h3>
            <p class="text-sm text-gray-500">{{ $user->department?->name ?? 'No department' }} &middot; Video watch history</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-4">
        <form method="GET" action="{{ route('admin.users.watch-history', $user) }}" class="flex items-center gap-3">
            <x-input name="date_from" type="date" :value="request('date_from')" placeholder="From" />
            <x-input name="date_to" type="date" :value="request('date_to')" placeholder="To" />
            <x-button type="submit" variant="primary" size="sm">Filter</x-button>
            <a href="{{ route('admin.users.watch-history', $user) }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        </form>
    </div>

    @if($watchHistory->isEmpty())
        <x-card>
            <x-empty-state title="No watch history" description="This user has not watched any videos yet." />
        </x-card>
    @else
        <x-data-table :headers="['video' => 'Video', 'category' => 'Category', 'started' => 'Started At', 'duration' => 'Duration', 'completed' => 'Completed']">
            @foreach($watchHistory as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        @if($log->video)
                            <a href="{{ route('admin.videos.show', $log->video) }}" class="font-medium text-gray-900 hover:text-primary-600">
                                {{ Str::limit($log->video->title, 40) }}
                            </a>
                        @else
                            <span class="text-gray-400">Deleted video</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">
                        {{ $log->video?->category?->name ?? '-' }}
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500 whitespace-nowrap">
                        {{ $log->started_at->format('M d, Y h:i A') }}
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-900 font-medium">
                        {{ $log->getFormattedDuration() }}
                    </td>
                    <td class="px-6 py-3">
                        @if($log->completed)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Yes</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">No</span>
                        @endif
                    </td>
                </tr>
            @endforeach

            <x-slot:pagination>
                {{ $watchHistory->withQueryString()->links() }}
            </x-slot:pagination>
        </x-data-table>
    @endif
@endsection
