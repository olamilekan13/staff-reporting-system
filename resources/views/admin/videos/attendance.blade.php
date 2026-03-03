@extends('layouts.app')

@section('title', 'Video Attendance')
@section('page-title', 'Video Attendance')

@section('content')
    <a href="{{ route('admin.videos.show', $video) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to {{ Str::limit($video->title, 30) }}
    </a>

    {{-- Video info + stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="card px-4 py-3 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_views'] }}</p>
            <p class="text-xs text-gray-500">Total Views</p>
        </div>
        <div class="card px-4 py-3 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['unique_viewers'] }}</p>
            <p class="text-xs text-gray-500">Unique Viewers</p>
        </div>
        <div class="card px-4 py-3 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ floor($stats['avg_duration'] / 60) }}m</p>
            <p class="text-xs text-gray-500">Avg Duration</p>
        </div>
        <div class="card px-4 py-3 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['completion_rate'] }}%</p>
            <p class="text-xs text-gray-500">Completion Rate</p>
        </div>
    </div>

    {{-- Filters + Export --}}
    <div class="flex items-center justify-between mb-4">
        <form method="GET" action="{{ route('admin.videos.attendance', $video) }}" class="flex items-center gap-3">
            <select name="department_id" onchange="this.form.submit()" class="input py-1.5 text-sm w-48">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
            <input type="text" name="search" placeholder="Search user..." class="input py-1.5 text-sm w-48"
                value="{{ request('search') }}">
            <x-button type="submit" variant="primary" size="sm">Filter</x-button>
        </form>

        <a href="{{ route('admin.videos.attendance.export', ['video' => $video, 'department_id' => request('department_id')]) }}"
           class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-800">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Export Excel
        </a>
    </div>

    {{-- Attendance table --}}
    @if($attendance->isEmpty())
        <x-card>
            <x-empty-state title="No attendance data" description="No one has watched this video yet." />
        </x-card>
    @else
        <x-data-table :headers="['user' => 'User', 'department' => 'Department', 'started' => 'Started At', 'duration' => 'Duration', 'completed' => 'Completed', 'last_active' => 'Last Active']">
            @foreach($attendance as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.users.watch-history', $log->user) }}" class="font-medium text-gray-900 hover:text-primary-600">
                            {{ $log->user?->full_name ?? 'Unknown' }}
                        </a>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">
                        {{ $log->user?->department?->name ?? '-' }}
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
                    <td class="px-6 py-3 text-sm text-gray-500 whitespace-nowrap">
                        @if($log->ended_at)
                            {{ $log->ended_at->format('M d, Y h:i A') }}
                        @elseif($log->last_heartbeat_at)
                            {{ $log->last_heartbeat_at->diffForHumans() }}
                        @else
                            -
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
