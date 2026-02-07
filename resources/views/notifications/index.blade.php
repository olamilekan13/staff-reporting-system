@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Stay updated with your notifications</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="inline">
                @csrf
                <x-button type="submit" variant="secondary" size="sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Mark All as Read
                </x-button>
            </form>
        </div>
    </div>

    {{-- Filter --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('notifications.index') }}" class="flex items-center gap-3">
            <div class="w-48">
                <x-select name="filter" placeholder="All Notifications" :selected="request('filter')"
                    :options="['unread' => 'Unread', 'read' => 'Read']" />
            </div>
            <div class="w-48">
                <x-select name="type" placeholder="All Types" :selected="request('type')"
                    :options="[
                        'comment' => 'Comments',
                        'announcement' => 'Announcements',
                        'report_status' => 'Report Status',
                        'proposal_status' => 'Proposal Status',
                        'system' => 'System'
                    ]" />
            </div>
            <x-button type="submit" variant="secondary" size="sm">Filter</x-button>
            @if(request('filter') || request('type'))
                <a href="{{ route('notifications.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
            @endif
        </form>
    </div>

    @if($notifications->isEmpty())
        <x-card>
            <x-empty-state title="No notifications" description="You don't have any notifications at this time." />
        </x-card>
    @else
        <div class="space-y-3">
            @foreach($notifications as $notification)
                @php
                    $link = $notification->getLink();
                @endphp
                <div class="relative card hover:shadow-md transition-shadow duration-200 {{ !$notification->isRead() ? 'bg-blue-50 border-l-4 border-l-primary-500' : 'bg-white' }} {{ $link ? 'cursor-pointer' : '' }}"
                     @if($link) onclick="window.location='{{ route('notifications.view', $notification) }}'" @endif>
                    <div class="px-6 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                {{-- Icon --}}
                                <div class="shrink-0 mt-0.5">
                                    <div class="w-10 h-10 rounded-full bg-{{ $notification->getColor() }}-100 flex items-center justify-center">
                                        @php
                                            $icon = $notification->getIcon();
                                            $iconSvg = match($icon) {
                                                'chat-bubble-left-ellipsis' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />',
                                                'megaphone' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" />',
                                                'document-text' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />',
                                                'light-bulb' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />',
                                                'cog' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
                                                default => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />',
                                            };
                                        @endphp
                                        <svg class="w-5 h-5 text-{{ $notification->getColor() }}-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            {!! $iconSvg !!}
                                        </svg>
                                    </div>
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-semibold mb-1 {{ !$notification->isRead() ? 'text-gray-900' : 'text-gray-600' }}">
                                        {{ $notification->title }}
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-2">
                                        {{ $notification->message }}
                                    </p>
                                    <div class="flex items-center gap-2 text-xs text-gray-400">
                                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                                        @if($link)
                                            <span>&middot;</span>
                                            <span class="text-primary-600 font-medium">Click to view details</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2 shrink-0">
                                @if(!$notification->isRead())
                                    <form method="POST" action="{{ route('notifications.mark-as-read', $notification) }}" class="inline" onclick="event.stopPropagation()">
                                        @csrf
                                        <button type="submit" class="relative z-10 text-gray-400 hover:text-primary-600 transition-colors" title="Mark as read">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $notifications->withQueryString()->links() }}
        </div>
    @endif
@endsection
