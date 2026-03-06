@props(['recentVideos' => collect()])

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
     x-data="{
         isLive: false,
         streamTitle: '',
         mode: 'embed',
         m3u8Url: '',
         embedCode: '',
         loading: true,
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
                     this.loading = false;
                     if (d.is_live && !wasLive && d.mode === 'm3u8') {
                         this.$nextTick(() => this.initHls());
                     }
                     if (!d.is_live && wasLive) {
                         this.destroyHls();
                     }
                 })
                 .catch(() => { this.loading = false; });
         },
         initHls() {
             const video = this.$refs.widgetHlsVideo;
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
     x-init="checkStream(); setInterval(() => checkStream(), 30000)">

    {{-- Header --}}
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
            <span class="text-sm font-semibold text-gray-700">Live &amp; Video</span>
        </div>
        <div>
            <span x-show="isLive && !loading"
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-500 text-white animate-pulse">
                &#9679; LIVE
            </span>
            <span x-show="!loading" class="text-xs text-gray-400">Recent Videos</span>
        </div>
    </div>

    {{-- Loading skeleton --}}
    <div x-show="loading" class="p-4 space-y-3">
        <div class="h-4 bg-gray-200 rounded animate-pulse"></div>
        <div class="h-4 bg-gray-200 rounded animate-pulse w-3/4"></div>
        <div class="h-4 bg-gray-200 rounded animate-pulse w-1/2"></div>
    </div>

    {{-- Live player --}}
    <div x-show="isLive && !loading">
        <div class="relative w-full" style="padding-bottom: 56.25%">
            {{-- M3U8 mode --}}
            <template x-if="mode === 'm3u8'">
                <video x-ref="widgetHlsVideo"
                       class="absolute inset-0 w-full h-full"
                       controls
                       playsinline
                       autoplay
                       muted></video>
            </template>
            {{-- Embed mode --}}
            <template x-if="mode === 'embed'">
                <div class="absolute inset-0 w-full h-full [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0"
                     x-html="embedCode"></div>
            </template>
        </div>
        <div class="px-4 py-2 flex items-center justify-between text-sm">
            <span class="font-medium text-gray-700 truncate" x-text="streamTitle"></span>
        </div>
        <div class="px-4 pb-3">
            <a href="{{ route('live.index') }}" class="text-sm text-primary-600 hover:underline">Open full player &rarr;</a>
        </div>
    </div>

    {{-- Divider between live player and video list --}}
    <div x-show="isLive && !loading" class="border-t border-gray-100"></div>

    {{-- Recent videos (always shown) --}}
    <div x-show="!loading">
        @if($recentVideos->isEmpty())
            <div class="p-6 text-center text-gray-400 text-sm">
                <p>No recent videos yet</p>
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
        <div class="px-4 py-3 border-t border-gray-50">
            <a href="{{ route('live.index') }}" class="text-xs text-gray-500 hover:text-primary-600">
                Go to Live &amp; Video page &rarr;
            </a>
        </div>
    </div>
</div>
