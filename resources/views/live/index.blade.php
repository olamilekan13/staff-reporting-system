@extends('layouts.app')

@section('title', 'Live Stream')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- Page header --}}
    <div class="mb-6 flex items-center gap-3">
        <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
        </svg>
        <h2 class="text-xl font-bold text-gray-900">Live &amp; Video</h2>
        @if($streamInfo['is_live'])
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-500 text-white animate-pulse">
                &#9679; LIVE
            </span>
        @endif
    </div>

    {{-- Two-column grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Player column (2/3 width) --}}
        <div class="lg:col-span-2"
             x-data="{
                 isLive: {{ $streamInfo['is_live'] ? 'true' : 'false' }},
                 streamTitle: '{{ addslashes($streamInfo['stream_title'] ?? '') }}',
                 mode: '{{ $streamInfo['mode'] ?? 'embed' }}',
                 m3u8Url: '{{ addslashes($streamInfo['m3u8_url'] ?? '') }}',
                 embedCode: `{{ $streamInfo['embed_code'] ?? '' }}`,
                 hlsPlayer: null,
                 checkStream() {
                     fetch('{{ route('stream.status') }}')
                         .then(r => r.json())
                         .then(d => {
                             const wasLive = this.isLive;
                             this.isLive = d.is_live;
                             this.streamTitle = d.stream_title;
                             this.mode = d.mode;
                             this.m3u8Url = d.m3u8_url;
                             this.embedCode = d.embed_code;
                             if (d.is_live && !wasLive && d.mode === 'm3u8') {
                                 this.$nextTick(() => this.initHls());
                             }
                             if (!d.is_live && wasLive) {
                                 this.destroyHls();
                             }
                         })
                         .catch(() => {});
                 },
                 initHls() {
                     const video = this.$refs.hlsVideo;
                     if (!video || !this.m3u8Url) return;
                     if (video.canPlayType('application/vnd.apple.mpegurl')) {
                         video.src = this.m3u8Url;
                         video.play();
                     } else if (window.Hls && Hls.isSupported()) {
                         this.destroyHls();
                         this.hlsPlayer = new Hls();
                         this.hlsPlayer.loadSource(this.m3u8Url);
                         this.hlsPlayer.attachMedia(video);
                         this.hlsPlayer.on(Hls.Events.MANIFEST_PARSED, () => video.play());
                     }
                 },
                 destroyHls() {
                     if (this.hlsPlayer) {
                         this.hlsPlayer.destroy();
                         this.hlsPlayer = null;
                     }
                 }
             }"
             x-init="setInterval(() => checkStream(), 30000); if (isLive && mode === 'm3u8') { $nextTick(() => initHls()); }">

            {{-- Live player --}}
            <div x-show="isLive" x-cloak>
                <div class="bg-black rounded-xl overflow-hidden shadow-sm">
                    <div class="relative w-full" style="padding-bottom: 56.25%">
                        {{-- M3U8 mode --}}
                        <template x-if="mode === 'm3u8'">
                            <video x-ref="hlsVideo"
                                   class="absolute inset-0 w-full h-full"
                                   controls
                                   playsinline
                                   autoplay></video>
                        </template>
                        {{-- Embed mode --}}
                        <template x-if="mode === 'embed'">
                            <div class="absolute inset-0 w-full h-full [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0"
                                 x-html="embedCode"></div>
                        </template>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                        </span>
                        <span class="font-semibold text-red-600">LIVE NOW</span>
                    </div>
                    <span class="font-medium text-gray-700 truncate max-w-xs" x-text="streamTitle"></span>
                </div>
            </div>

            {{-- Offline placeholder --}}
            <div x-show="!isLive" x-cloak>
                <div class="bg-gray-800 rounded-xl overflow-hidden shadow-sm">
                    <div class="relative w-full" style="padding-bottom: 56.25%">
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 gap-3">
                            <svg class="w-14 h-14 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M12 18.75H4.5a2.25 2.25 0 0 1-2.25-2.25V9m12.841 9.091L16.5 19.5m-1.409-1.409c.407-.407.659-.97.659-1.591v-9a2.25 2.25 0 0 0-2.25-2.25h-9c-.621 0-1.184.252-1.591.659m12.182 12.182L2.909 5.909M1.5 4.5l1.409 1.409" />
                            </svg>
                            <p class="text-lg font-semibold text-gray-300">Stream is Offline</p>
                            <p class="text-sm text-gray-500">Check back soon for the next live broadcast</p>
                        </div>
                    </div>
                </div>
                @if($upcomingStreams->isNotEmpty())
                    @php $next = $upcomingStreams->first(); @endphp
                    <p class="mt-3 text-sm text-gray-500">
                        Next stream:
                        <span class="font-medium text-gray-700">{{ $next->title }}</span>
                        &mdash;
                        {{ $next->starts_at?->format('M j, Y \a\t g:i A') ?? 'TBA' }}
                    </p>
                @endif
            </div>

        </div>

        {{-- Sidebar column (1/3 width) --}}
        <div class="lg:col-span-1" x-data="{ activeTab: 'videos' }">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

                {{-- Tab bar --}}
                <div class="flex border-b border-gray-100">
                    <button @click="activeTab = 'videos'"
                        :class="activeTab === 'videos'
                            ? 'border-b-2 border-primary-500 text-primary-600 font-semibold'
                            : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700'"
                        class="flex-1 px-4 py-3 text-sm text-center transition-colors duration-150">
                        Past Videos
                    </button>
                    <button @click="activeTab = 'upcoming'"
                        :class="activeTab === 'upcoming'
                            ? 'border-b-2 border-primary-500 text-primary-600 font-semibold'
                            : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700'"
                        class="flex-1 px-4 py-3 text-sm text-center transition-colors duration-150">
                        Upcoming
                        @if($upcomingStreams->isNotEmpty())
                            <span class="ml-1 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-primary-500 rounded-full">
                                {{ $upcomingStreams->count() }}
                            </span>
                        @endif
                    </button>
                </div>

                {{-- Past Videos panel --}}
                <div x-show="activeTab === 'videos'" x-cloak>
                    @if($recentVideos->isEmpty())
                        <div class="px-4 py-10 text-center text-sm text-gray-400">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                            </svg>
                            No past videos yet.
                        </div>
                    @else
                        <ul class="divide-y divide-gray-50">
                            @foreach($recentVideos as $video)
                                <li class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                        @if($video->announcement_type === 'audio_upload')
                                            <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 9 10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-700 truncate">{{ $video->title }}</p>
                                        <p class="text-xs text-gray-400">{{ $video->created_at->diffForHumans() }}</p>
                                    </div>
                                    <a href="{{ route('announcements.show', $video) }}"
                                       class="text-xs text-primary-600 hover:underline flex-shrink-0">
                                        Watch
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Upcoming Streams panel --}}
                <div x-show="activeTab === 'upcoming'" x-cloak>
                    @if($upcomingStreams->isEmpty())
                        <div class="px-4 py-10 text-center text-sm text-gray-400">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            No upcoming streams scheduled.
                        </div>
                    @else
                        <ul class="divide-y divide-gray-50">
                            @foreach($upcomingStreams as $stream)
                                <li class="px-4 py-3 hover:bg-gray-50 transition-colors">
                                    <p class="text-sm font-medium text-gray-700 truncate">{{ $stream->title }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $stream->starts_at?->format('M j, Y \a\t g:i A') ?? 'Date TBA' }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest/dist/hls.min.js"></script>
@endpush
