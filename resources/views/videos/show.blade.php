@extends('layouts.app')

@section('title', $video->title)
@section('page-title', 'Watch Video')

@section('content')
<div class="max-w-5xl mx-auto"
     x-data="watchTracker({{ $video->id }})"
     x-init="init()"
     @beforeunload.window="endTracking(false)">

    <a href="{{ route('videos.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to Videos
    </a>

    {{-- Video Player --}}
    <div class="bg-black rounded-xl overflow-hidden shadow-sm">
        <div class="relative w-full" style="padding-bottom: 56.25%">
            @if($video->source_type === 'upload' && $video->getVideoUrl())
                <video x-ref="videoPlayer"
                       class="absolute inset-0 w-full h-full"
                       controls
                       playsinline
                       @play="onPlay()"
                       @pause="onPause()"
                       @ended="onEnded()">
                    <source src="{{ $video->getVideoUrl() }}" type="video/mp4">
                </video>

            @elseif($video->source_type === 'youtube' && $video->getYouTubeEmbedUrl())
                <iframe x-ref="ytPlayer"
                        id="yt-player"
                        class="absolute inset-0 w-full h-full border-0"
                        src="{{ $video->getYouTubeEmbedUrl() }}&enablejsapi=1&origin={{ url('/') }}"
                        allowfullscreen
                        allow="autoplay"></iframe>

            @elseif($video->source_type === 'vimeo' && $video->getVimeoEmbedUrl())
                <iframe x-ref="vimeoPlayer"
                        class="absolute inset-0 w-full h-full border-0"
                        src="{{ $video->getVimeoEmbedUrl() }}?api=1"
                        allowfullscreen
                        allow="autoplay"></iframe>

            @elseif($video->source_type === 'embed' && $video->source_url)
                <div class="absolute inset-0 w-full h-full [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0">
                    {!! $video->source_url !!}
                </div>

            @elseif($video->source_type === 'm3u8' && $video->source_url)
                <video x-ref="videoPlayer"
                       class="absolute inset-0 w-full h-full"
                       controls
                       playsinline
                       @play="onPlay()"
                       @pause="onPause()"
                       @ended="onEnded()"></video>

            @else
                <div class="absolute inset-0 flex items-center justify-center text-gray-400">
                    <p>Video not available</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Video info --}}
    <div class="mt-4">
        <h1 class="text-xl font-bold text-gray-900">{{ $video->title }}</h1>
        <div class="flex items-center gap-3 mt-2 text-sm text-gray-500">
            @if($video->category)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700">
                    {{ $video->category->name }}
                </span>
            @endif
            @if($video->duration_seconds)
                <span>{{ $video->getFormattedDuration() }}</span>
            @endif
            <span>{{ $video->created_at->format('M d, Y') }}</span>
        </div>

        @if($video->description)
            <div class="mt-4 prose prose-sm max-w-none text-gray-600">
                {!! $video->description !!}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if($video->source_type === 'm3u8')
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest/dist/hls.min.js"></script>
@endif
<script>
function watchTracker(videoId) {
    return {
        videoId: videoId,
        sessionId: null,
        heartbeatInterval: null,
        isPlaying: false,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content,

        init() {
            @if($video->source_type === 'm3u8' && $video->source_url)
                this.initHls();
            @endif

            @if(in_array($video->source_type, ['youtube', 'vimeo', 'embed']))
                // For iframe-based players, start tracking on page load
                this.startTracking();
            @endif

            // Handle page visibility changes
            document.addEventListener('visibilitychange', () => {
                if (document.hidden && this.sessionId) {
                    // Page hidden — send end beacon
                    this.sendBeacon(false);
                }
            });
        },

        initHls() {
            const video = this.$refs.videoPlayer;
            if (!video) return;
            const url = '{{ $video->source_url }}';
            if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = url;
            } else if (window.Hls && Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(url);
                hls.attachMedia(video);
            }
        },

        async startTracking() {
            if (this.sessionId) return;
            try {
                const res = await fetch('/api/v1/watch/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ source: 'vod', video_id: this.videoId }),
                });
                const data = await res.json();
                if (data.success && data.data) {
                    this.sessionId = data.data.session_id;
                    this.startHeartbeat();
                }
            } catch (e) {
                console.error('Watch tracking start failed:', e);
            }
        },

        startHeartbeat() {
            if (this.heartbeatInterval) clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = setInterval(() => {
                if (this.sessionId) {
                    fetch('/api/v1/watch/heartbeat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ session_id: this.sessionId }),
                    }).catch(() => {});
                }
            }, 30000);
        },

        async endTracking(completed) {
            if (!this.sessionId) return;
            if (this.heartbeatInterval) {
                clearInterval(this.heartbeatInterval);
                this.heartbeatInterval = null;
            }
            this.sendBeacon(completed);
            this.sessionId = null;
        },

        sendBeacon(completed) {
            if (!this.sessionId) return;
            const payload = JSON.stringify({
                session_id: this.sessionId,
                completed: !!completed,
            });
            // Use sendBeacon for reliability during page unload
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/api/v1/watch/end',
                    new Blob([payload], { type: 'application/json' }));
            } else {
                fetch('/api/v1/watch/end', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: payload,
                    keepalive: true,
                }).catch(() => {});
            }
        },

        // HTML5 video events
        onPlay() {
            this.isPlaying = true;
            this.startTracking();
        },
        onPause() {
            this.isPlaying = false;
        },
        onEnded() {
            this.isPlaying = false;
            this.endTracking(true);
        },
    };
}
</script>
@endpush
