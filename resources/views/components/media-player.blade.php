@props(['announcement', 'autoplay' => false])

@php
    $type = $announcement->announcement_type;

    if ($type === 'text') {
        return;
    }

    $youtubeEmbedUrl = $announcement->getYouTubeEmbedUrl();
    $vimeoEmbedUrl   = $announcement->getVimeoEmbedUrl();
    $uploadedUrl     = $announcement->getUploadedMediaUrl();
@endphp

<div {{ $attributes }}>
    {{-- Video upload --}}
    @if($type === 'video_upload')
        @if($uploadedUrl)
            <div class="w-full">
                <video controls preload="metadata" class="w-full rounded-lg bg-black max-h-[500px]"
                    @if($autoplay) autoplay @endif>
                    <source src="{{ $uploadedUrl }}">
                    Your browser does not support video playback.
                </video>
            </div>
        @endif

    {{-- Audio upload --}}
    @elseif($type === 'audio_upload')
        @if($uploadedUrl)
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg border">
                <div class="flex-shrink-0 w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 9 10.5-3m0 6.553v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 1 1-.99-3.467l2.31-.66a2.25 2.25 0 0 0 1.632-2.163Zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 0 1-1.632 2.163l-1.32.377a1.803 1.803 0 0 1-.99-3.467l2.31-.66A2.25 2.25 0 0 0 9 15.553Z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <audio controls class="w-full" @if($autoplay) autoplay @endif>
                        <source src="{{ $uploadedUrl }}">
                        Your browser does not support audio playback.
                    </audio>
                </div>
            </div>
        @endif

    {{-- YouTube embed --}}
    @elseif($type === 'youtube')
        @if($youtubeEmbedUrl)
            <div class="relative w-full rounded-lg overflow-hidden" style="padding-bottom: 56.25%">
                <iframe class="absolute inset-0 w-full h-full"
                    src="{{ $youtubeEmbedUrl }}"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                    loading="lazy">
                </iframe>
            </div>
        @endif

    {{-- Vimeo embed --}}
    @elseif($type === 'vimeo')
        @if($vimeoEmbedUrl)
            <div class="relative w-full rounded-lg overflow-hidden" style="padding-bottom: 56.25%">
                <iframe class="absolute inset-0 w-full h-full"
                    src="{{ $vimeoEmbedUrl }}"
                    allow="autoplay; fullscreen; picture-in-picture"
                    allowfullscreen
                    loading="lazy">
                </iframe>
            </div>
        @endif

    {{-- Livestream (Owncast) --}}
    @elseif($type === 'livestream')
        <div x-data="{
            isLive: false,
            viewers: 0,
            init() {
                fetch('{{ route('stream.status') }}')
                    .then(r => r.json())
                    .then(d => { this.isLive = d.is_live; this.viewers = d.viewer_count })
                    .catch(() => {})
            }
        }">
            <template x-if="isLive">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                        </span>
                        <span class="text-sm font-semibold text-red-600">LIVE NOW</span>
                        <span class="text-sm text-gray-500" x-text="viewers + ' watching'"></span>
                    </div>
                    <div class="relative w-full rounded-lg overflow-hidden" style="padding-bottom: 56.25%">
                        <iframe class="absolute inset-0 w-full h-full"
                            src="{{ config('services.owncast.embed_url') }}"
                            allowfullscreen>
                        </iframe>
                    </div>
                    <a href="{{ route('live.index') }}" class="mt-2 inline-block text-sm text-primary-600 hover:underline">
                        Open full player &rarr;
                    </a>
                </div>
            </template>
            <template x-if="!isLive">
                <div class="flex flex-col items-center justify-center h-48 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200 text-gray-400">
                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M12 18.75H4.5a2.25 2.25 0 0 1-2.25-2.25V9m12.841 9.091L16.5 19.5m-1.409-1.409c.407-.407.659-.97.659-1.591v-9a2.25 2.25 0 0 0-2.25-2.25h-9c-.621 0-1.184.252-1.591.659m12.182 12.182L2.909 5.909M1.5 4.5l1.409 1.409" />
                    </svg>
                    <p class="mt-2 font-medium">Stream is currently offline</p>
                    <a href="{{ route('live.index') }}" class="mt-1 text-sm text-primary-600 hover:underline">
                        Go to Live Stream page
                    </a>
                </div>
            </template>
        </div>
    @endif

    {{-- Optional caption --}}
    @if($announcement->media_title)
        <p class="mt-2 text-sm text-gray-600">{{ $announcement->media_title }}</p>
    @endif
</div>
