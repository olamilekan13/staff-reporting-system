@extends('layouts.app')

@section('title', $announcement->title)
@section('page-title', 'Announcement')

@section('content')
    <div class="max-w-3xl">
        {{-- Back link --}}
        <a href="{{ route('announcements.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Announcements
        </a>

        <x-card>
            {{-- Header --}}
            <div class="flex items-start justify-between gap-4 mb-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-bold text-gray-900">{{ $announcement->title }}</h1>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if($announcement->is_pinned)
                        <span class="badge badge-primary">Pinned</span>
                    @endif
                    <span class="badge {{ $announcement->getPriorityBadgeClass() }}">
                        {{ ucfirst($announcement->priority) }}
                    </span>
                </div>
            </div>

            {{-- Meta --}}
            <div class="flex items-center gap-3 text-sm text-gray-500 mb-6 pb-4 border-b border-gray-100">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    <span>{{ $announcement->creator?->full_name }}</span>
                </div>
                <span>&middot;</span>
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span>{{ $announcement->created_at->format('M d, Y \a\t h:i A') }}</span>
                </div>
            </div>

            @if($announcement->isExpired())
                <x-alert type="warning" class="mb-4">
                    This announcement expired on {{ $announcement->expires_at->format('M d, Y') }}.
                </x-alert>
            @endif

            {{-- Content --}}
            <div class="prose prose-sm max-w-none text-gray-700">
                {!! $announcement->content !!}
            </div>
        </x-card>
    </div>
@endsection
