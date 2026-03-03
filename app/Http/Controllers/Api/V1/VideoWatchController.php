<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Video;
use App\Services\WatchTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VideoWatchController extends ApiController
{
    public function __construct(
        protected WatchTrackingService $trackingService
    ) {}

    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'source' => 'required|in:vod,livestream',
            'video_id' => 'required_if:source,vod|nullable|integer|exists:videos,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $data = $validator->validated();

        // For VOD, verify user can access the video
        if ($data['source'] === 'vod' && !empty($data['video_id'])) {
            $video = Video::find($data['video_id']);
            if (!$video || !$video->isPublished()) {
                return $this->notFoundResponse('Video not found.');
            }
        }

        $log = $this->trackingService->startSession(
            Auth::user(),
            $data['source'],
            $data['video_id'] ?? null
        );

        return $this->successResponse([
            'session_id' => $log->session_id,
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:64',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $log = $this->trackingService->heartbeat(
            $request->input('session_id'),
            Auth::user()
        );

        if (!$log) {
            return $this->notFoundResponse('Session not found.');
        }

        return $this->successResponse(['ok' => true]);
    }

    public function end(Request $request): JsonResponse
    {
        // Parse JSON body (supports both regular fetch and sendBeacon)
        $input = $request->all();
        if (empty($input)) {
            $input = json_decode($request->getContent(), true) ?? [];
        }

        $validator = Validator::make($input, [
            'session_id' => 'required|string|max:64',
            'completed' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $data = $validator->validated();

        // For sendBeacon, auth may not be available — validate by session_id only
        $user = Auth::user();

        $log = $this->trackingService->endSession(
            $data['session_id'],
            $user,
            $data['completed'] ?? false
        );

        return $this->successResponse(['ok' => true]);
    }
}
