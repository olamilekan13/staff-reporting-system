<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Services\LiveStreamService;
use Illuminate\Http\JsonResponse;

class StreamController extends ApiController
{
    public function status(LiveStreamService $stream): JsonResponse
    {
        return $this->successResponse($stream->getStreamInfo(), 'Stream status retrieved successfully');
    }

    public function videos(): JsonResponse
    {
        $videos = Announcement::whereIn('announcement_type', [
            'video_upload', 'audio_upload', 'youtube', 'vimeo',
        ])
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->paginate(15);

        return $this->paginatedResponse(
            AnnouncementResource::collection($videos),
            'Videos retrieved successfully'
        );
    }
}
