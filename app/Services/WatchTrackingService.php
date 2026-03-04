<?php

namespace App\Services;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoWatchLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class WatchTrackingService
{
    public function startSession(User $user, string $source, ?int $videoId = null): VideoWatchLog
    {
        // Close any existing open sessions for the same user+video to prevent duplicates
        $existingQuery = VideoWatchLog::where('user_id', $user->id)
            ->whereNull('ended_at');

        if ($source === VideoWatchLog::SOURCE_VOD && $videoId) {
            $existingQuery->where('watchable_type', Video::class)
                ->where('watchable_id', $videoId);
        } elseif ($source === VideoWatchLog::SOURCE_LIVESTREAM) {
            $existingQuery->where('source', VideoWatchLog::SOURCE_LIVESTREAM);
        }

        $existingQuery->update([
            'ended_at' => now(),
        ]);

        return VideoWatchLog::create([
            'user_id' => $user->id,
            'watchable_type' => $source === VideoWatchLog::SOURCE_VOD ? Video::class : 'livestream',
            'watchable_id' => $videoId,
            'session_id' => Str::uuid()->toString(),
            'started_at' => now(),
            'source' => $source,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function heartbeat(string $sessionId, ?User $user = null): ?VideoWatchLog
    {
        $query = VideoWatchLog::where('session_id', $sessionId);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $log = $query->first();

        if (!$log) {
            return null;
        }

        $log->update([
            'last_heartbeat_at' => now(),
            'duration_seconds' => min((int) now()->diffInSeconds($log->started_at), 4294967295),
        ]);

        return $log;
    }

    public function endSession(string $sessionId, ?User $user = null, bool $completed = false): ?VideoWatchLog
    {
        $query = VideoWatchLog::where('session_id', $sessionId);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $log = $query->first();

        if (!$log || $log->ended_at) {
            return $log;
        }

        $log->update([
            'ended_at' => now(),
            'duration_seconds' => min((int) now()->diffInSeconds($log->started_at), 4294967295),
            'completed' => $completed,
        ]);

        return $log;
    }

    public function getVideoAttendance(int $videoId, array $filters = []): LengthAwarePaginator
    {
        $query = VideoWatchLog::query()
            ->with(['user', 'user.department'])
            ->forVideo($videoId);

        if (!empty($filters['department_id'])) {
            $query->whereHas('user', fn($q) => $q->where('department_id', $filters['department_id']));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('started_at');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function getUserWatchHistory(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = VideoWatchLog::query()
            ->with(['video', 'video.category'])
            ->forUser($userId)
            ->forVod();

        if (!empty($filters['date_from'])) {
            $query->where('started_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('started_at', '<=', $filters['date_to']);
        }

        $query->orderByDesc('started_at');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function getLivestreamAttendance(array $filters = []): LengthAwarePaginator
    {
        $query = VideoWatchLog::query()
            ->with(['user', 'user.department'])
            ->forLivestream();

        if (!empty($filters['department_id'])) {
            $query->whereHas('user', fn($q) => $q->where('department_id', $filters['department_id']));
        }

        if (!empty($filters['date_from'])) {
            $query->where('started_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('started_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('started_at');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function getVideoStats(Video $video): array
    {
        $logs = VideoWatchLog::forVideo($video->id);

        return [
            'total_views' => $logs->count(),
            'unique_viewers' => $logs->distinct('user_id')->count('user_id'),
            'avg_duration' => (int) $logs->avg('duration_seconds'),
            'completion_rate' => $logs->count() > 0
                ? round(($logs->clone()->completed()->count() / $logs->count()) * 100, 1)
                : 0,
        ];
    }

    public function getDepartmentAttendance(int $departmentId, array $filters = []): LengthAwarePaginator
    {
        $query = VideoWatchLog::query()
            ->with(['user', 'video', 'video.category'])
            ->whereHas('user', fn($q) => $q->where('department_id', $departmentId));

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('started_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('started_at', '<=', $filters['date_to']);
        }

        $query->orderByDesc('started_at');

        return $query->paginate($filters['per_page'] ?? 20);
    }
}
