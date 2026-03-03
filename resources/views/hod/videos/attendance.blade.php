@extends('layouts.app')

@section('title', 'Department Watch Activity')
@section('page-title', 'Department Watch Activity')

@section('content')
    <div class="mb-6">
        <p class="text-sm text-gray-500">Video and livestream watch activity for your department members</p>
    </div>

    {{-- Filters --}}
    <div class="mb-4">
        <form method="GET" action="{{ route('hod.video-attendance') }}" class="flex flex-wrap items-center gap-3">
            <select name="source" class="input py-1.5 text-sm w-40">
                <option value="">All Sources</option>
                <option value="vod" {{ request('source') === 'vod' ? 'selected' : '' }}>Videos</option>
                <option value="livestream" {{ request('source') === 'livestream' ? 'selected' : '' }}>Live Streams</option>
            </select>
            <input type="date" name="date_from" class="input py-1.5 text-sm" value="{{ request('date_from') }}">
            <input type="date" name="date_to" class="input py-1.5 text-sm" value="{{ request('date_to') }}">
            <x-button type="submit" variant="primary" size="sm">Filter</x-button>
            <a href="{{ route('hod.video-attendance') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        </form>
    </div>

    @if($attendance->isEmpty())
        <x-card>
            <x-empty-state title="No watch activity" description="No video or livestream watch activity recorded for your department yet." />
        </x-card>
    @else
        <x-data-table :headers="['user' => 'User', 'content' => 'Content', 'source' => 'Source', 'started' => 'Started At', 'duration' => 'Duration', 'completed' => 'Completed']">
            @foreach($attendance as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-900">
                        {{ $log->user?->full_name ?? 'Unknown' }}
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">
                        @if($log->source === 'vod' && $log->video)
                            {{ Str::limit($log->video->title, 30) }}
                        @elseif($log->source === 'livestream')
                            Live Stream
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        @if($log->source === 'livestream')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Live</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">VOD</span>
                        @endif
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
                {{ $attendance->withQueryString()->links() }}
            </x-slot:pagination>
        </x-data-table>
    @endif
@endsection
