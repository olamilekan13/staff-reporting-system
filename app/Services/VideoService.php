<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Video;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VideoService
{
    public function getAllVideos(array $filters = []): LengthAwarePaginator
    {
        $query = Video::query()->with(['category', 'creator', 'departments', 'users']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('video_category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        $query->orderByDesc('created_at');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getVideosForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Video::query()
            ->with(['category', 'creator'])
            ->published()
            ->forUser($user);

        if (!empty($filters['category_id'])) {
            $query->where('video_category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        $query->orderByDesc('created_at');

        return $query->paginate($filters['per_page'] ?? 12);
    }

    public function createVideo(User $creator, array $data): Video
    {
        $video = Video::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'video_category_id' => $data['video_category_id'] ?? null,
            'source_type' => $data['source_type'],
            'source_url' => $data['source_url'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'status' => $data['status'] ?? Video::STATUS_DRAFT,
            'target_type' => $data['target_type'] ?? Video::TARGET_ALL,
            'publish_at' => $data['publish_at'] ?? null,
            'created_by' => $creator->id,
        ]);

        $this->attachTargets($video, $data);

        ActivityLog::log(ActivityLog::ACTION_CREATE, $video);

        return $video->fresh(['category', 'creator', 'departments', 'users']);
    }

    public function updateVideo(Video $video, array $data): Video
    {
        $oldValues = $video->only([
            'title', 'description', 'video_category_id', 'source_type',
            'source_url', 'duration_seconds', 'status', 'target_type', 'publish_at',
        ]);

        $video->update([
            'title' => $data['title'] ?? $video->title,
            'description' => array_key_exists('description', $data) ? $data['description'] : $video->description,
            'video_category_id' => array_key_exists('video_category_id', $data) ? $data['video_category_id'] : $video->video_category_id,
            'source_type' => $data['source_type'] ?? $video->source_type,
            'source_url' => array_key_exists('source_url', $data) ? $data['source_url'] : $video->source_url,
            'duration_seconds' => array_key_exists('duration_seconds', $data) ? $data['duration_seconds'] : $video->duration_seconds,
            'status' => $data['status'] ?? $video->status,
            'target_type' => $data['target_type'] ?? $video->target_type,
            'publish_at' => array_key_exists('publish_at', $data) ? $data['publish_at'] : $video->publish_at,
        ]);

        if (isset($data['target_type']) || isset($data['user_ids']) || isset($data['department_ids'])) {
            $this->updateTargets($video, $data);
        }

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $video,
            $oldValues,
            $video->only([
                'title', 'description', 'video_category_id', 'source_type',
                'source_url', 'duration_seconds', 'status', 'target_type', 'publish_at',
            ])
        );

        return $video->fresh(['category', 'creator', 'departments', 'users']);
    }

    public function deleteVideo(Video $video): bool
    {
        ActivityLog::log(ActivityLog::ACTION_DELETE, $video);

        return $video->delete();
    }

    private function attachTargets(Video $video, array $data): void
    {
        switch ($video->target_type) {
            case Video::TARGET_USERS:
                if (!empty($data['user_ids'])) {
                    $video->users()->attach($data['user_ids']);
                }
                break;

            case Video::TARGET_DEPARTMENTS:
                if (!empty($data['department_ids'])) {
                    $video->departments()->attach($data['department_ids']);
                }
                break;
        }
    }

    private function updateTargets(Video $video, array $data): void
    {
        $video->users()->detach();
        $video->departments()->detach();

        $this->attachTargets($video, $data);
    }

    public function getTargetedUsers(Video $video): Collection
    {
        switch ($video->target_type) {
            case Video::TARGET_ALL:
                return User::active()->get();

            case Video::TARGET_USERS:
                return $video->users()->where('is_active', true)->get();

            case Video::TARGET_DEPARTMENTS:
                $departmentIds = $video->departments()->pluck('departments.id');
                return User::active()
                    ->whereIn('department_id', $departmentIds)
                    ->get();

            default:
                return collect();
        }
    }
}
