@extends('layouts.app')

@section('title', 'Live Stream Attendance')
@section('page-title', 'Live Stream Attendance')

@section('content')
    <a href="{{ route('admin.videos.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to Videos
    </a>

    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Track who joined live streams, when, and how long they stayed</p>
        </div>
    </div>

    {{-- Filters + Export --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
        <form method="GET" action="{{ route('admin.livestream-attendance') }}" class="flex flex-wrap items-center gap-3">
            <select name="department_id" class="input py-1.5 text-sm w-48">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="date_from" class="input py-1.5 text-sm" value="{{ request('date_from') }}" placeholder="From">
            <input type="date" name="date_to" class="input py-1.5 text-sm" value="{{ request('date_to') }}" placeholder="To">
            <input type="text" name="search" placeholder="Search user..." class="input py-1.5 text-sm w-48" value="{{ request('search') }}">
            <x-button type="submit" variant="primary" size="sm">Filter</x-button>
            <a href="{{ route('admin.livestream-attendance') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        </form>

        <a href="{{ route('admin.livestream-attendance.export', request()->query()) }}"
           class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-800 shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Export Excel
        </a>
    </div>

    @if($attendance->isEmpty())
        <x-card>
            <x-empty-state title="No livestream attendance" description="No livestream watch data has been recorded yet." />
        </x-card>
    @else
        <x-data-table :headers="['user' => 'User', 'department' => 'Department', 'joined' => 'Joined At', 'left' => 'Left At', 'duration' => 'Duration']">
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
                    <td class="px-6 py-3 text-sm text-gray-500 whitespace-nowrap">
                        @if($log->ended_at)
                            {{ $log->ended_at->format('M d, Y h:i A') }}
                        @else
                            <span class="text-orange-500">Still watching</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-900 font-medium">
                        {{ $log->getFormattedDuration() }}
                    </td>
                </tr>
            @endforeach

            <x-slot:pagination>
                {{ $attendance->withQueryString()->links() }}
            </x-slot:pagination>
        </x-data-table>
    @endif
@endsection
