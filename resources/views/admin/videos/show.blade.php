@extends('layouts.app')

@section('title', $video->title)
@section('page-title', 'Video Details')

@section('content')
    <a href="{{ route('admin.videos.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to Videos
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Video player --}}
        <div class="lg:col-span-2">
            <x-card>
                <div class="bg-black rounded-lg overflow-hidden mb-4">
                    <div class="relative w-full" style="padding-bottom: 56.25%">
                        @if($video->source_type === 'upload' && $video->getVideoUrl())
                            <video class="absolute inset-0 w-full h-full" controls>
                                <source src="{{ $video->getVideoUrl() }}" type="video/mp4">
                            </video>
                        @elseif($video->source_type === 'youtube' && $video->getYouTubeEmbedUrl())
                            <iframe class="absolute inset-0 w-full h-full border-0"
                                src="{{ $video->getYouTubeEmbedUrl() }}" allowfullscreen></iframe>
                        @elseif($video->source_type === 'vimeo' && $video->getVimeoEmbedUrl())
                            <iframe class="absolute inset-0 w-full h-full border-0"
                                src="{{ $video->getVimeoEmbedUrl() }}" allowfullscreen></iframe>
                        @elseif($video->source_type === 'embed' && $video->source_url)
                            <div class="absolute inset-0 w-full h-full [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0">
                                {!! $video->source_url !!}
                            </div>
                        @elseif($video->source_type === 'm3u8')
                            <div class="absolute inset-0 flex items-center justify-center text-gray-400"
                                 x-data="{ init() {
                                     const video = this.$refs.hlsVideo;
                                     if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                         video.src = '{{ $video->source_url }}';
                                     } else if (window.Hls && Hls.isSupported()) {
                                         const hls = new Hls();
                                         hls.loadSource('{{ $video->source_url }}');
                                         hls.attachMedia(video);
                                     }
                                 }}">
                                <video x-ref="hlsVideo" class="w-full h-full" controls></video>
                            </div>
                        @else
                            <div class="absolute inset-0 flex items-center justify-center text-gray-400">
                                <p>No preview available</p>
                            </div>
                        @endif
                    </div>
                </div>

                <h2 class="text-xl font-bold text-gray-900">{{ $video->title }}</h2>

                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                    @if($video->category)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700">
                            {{ $video->category->name }}
                        </span>
                    @endif
                    @if($video->duration_seconds)
                        <span>{{ $video->getFormattedDuration() }}</span>
                    @endif
                    <span>Added {{ $video->created_at->format('M d, Y') }}</span>
                    <span>by {{ $video->creator?->full_name }}</span>
                </div>

                @if($video->description)
                    <div class="mt-4 prose prose-sm max-w-none text-gray-600">
                        {!! $video->description !!}
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Stats sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Status & Actions --}}
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    @php $statusBadge = $video->getStatusBadge(); @endphp
                    <x-badge :type="$statusBadge['type']">{{ $statusBadge['label'] }}</x-badge>
                    @php $sourceBadge = $video->getSourceTypeBadge(); @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $sourceBadge['class'] }}">
                        {{ $sourceBadge['label'] }}
                    </span>
                </div>

                <div class="space-y-2">
                    @can('update', $video)
                        <a href="{{ route('admin.videos.edit', $video) }}" class="block w-full text-center px-4 py-2 text-sm font-medium text-primary-700 bg-primary-50 rounded-lg hover:bg-primary-100">
                            Edit Video
                        </a>
                    @endcan
                    <a href="{{ route('admin.videos.attendance', $video) }}" class="block w-full text-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                        View Attendance
                    </a>
                </div>
            </x-card>

            {{-- Watch Stats --}}
            <x-card title="Watch Statistics">
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_views'] }}</p>
                        <p class="text-xs text-gray-500">Total Views</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['unique_viewers'] }}</p>
                        <p class="text-xs text-gray-500">Unique Viewers</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        @php
                            $avgMin = floor($stats['avg_duration'] / 60);
                            $avgSec = $stats['avg_duration'] % 60;
                        @endphp
                        <p class="text-2xl font-bold text-gray-900">{{ $avgMin }}m</p>
                        <p class="text-xs text-gray-500">Avg Duration</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['completion_rate'] }}%</p>
                        <p class="text-xs text-gray-500">Completion Rate</p>
                    </div>
                </div>
            </x-card>

            {{-- Target Info --}}
            <x-card title="Target Audience">
                @if($video->target_type === 'all')
                    <p class="text-sm text-gray-600">All Users</p>
                @elseif($video->target_type === 'departments')
                    <ul class="space-y-1">
                        @foreach($video->departments as $dept)
                            <li class="text-sm text-gray-600">{{ $dept->name }}</li>
                        @endforeach
                    </ul>
                @elseif($video->target_type === 'users')
                    <ul class="space-y-1">
                        @foreach($video->users as $user)
                            <li class="text-sm text-gray-600">{{ $user->full_name }}</li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
@endsection

@if($video->source_type === 'm3u8')
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest/dist/hls.min.js"></script>
    @endpush
@endif
