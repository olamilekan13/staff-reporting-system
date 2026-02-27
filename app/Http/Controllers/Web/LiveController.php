<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\LiveStreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LiveController extends Controller
{
    public function status(LiveStreamService $stream): JsonResponse
    {
        return response()->json($stream->getStreamInfo());
    }

    public function index(LiveStreamService $stream): View
    {
        $streamInfo = $stream->getStreamInfo();

        $recentVideos = Announcement::whereIn('announcement_type', [
            'video_upload', 'audio_upload', 'youtube', 'vimeo',
        ])
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->limit(10)
            ->get();

        $upcomingStreams = Announcement::where('announcement_type', 'livestream')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        return view('live.index', compact('streamInfo', 'recentVideos', 'upcomingStreams'));
    }
}
