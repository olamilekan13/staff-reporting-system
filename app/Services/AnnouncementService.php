<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AnnouncementService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}
    /**
     * Get announcements for a user based on their role and department.
     */
    public function getAnnouncementsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Announcement::query()
            ->with(['creator'])
            ->forUser($user)
            ->active();

        if (!empty($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        // Order: pinned first, then by priority, then by date
        $query->orderByDesc('is_pinned')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderByDesc('created_at');

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get all announcements for admin management (includes expired/scheduled).
     */
    public function getAllAnnouncements(array $filters = []): LengthAwarePaginator
    {
        $query = Announcement::query()->with(['creator', 'departments', 'users']);

        if (!empty($filters['status'])) {
            match ($filters['status']) {
                'active' => $query->active(),
                'scheduled' => $query->where('starts_at', '>', now()),
                'expired' => $query->where('expires_at', '<', now()),
                default => null,
            };
        }

        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        $query->orderByDesc('created_at');

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get a single announcement and mark it as read.
     */
    public function getAnnouncementForUser(Announcement $announcement, User $user): Announcement
    {
        // Mark as read automatically when viewing
        $announcement->markAsReadBy($user);

        $announcement->load(['creator', 'departments']);

        return $announcement;
    }

    /**
     * Mark an announcement as read for a user.
     */
    public function markAsRead(Announcement $announcement, User $user): void
    {
        $announcement->markAsReadBy($user);
    }

    /**
     * Create a new announcement with targets.
     */
    public function createAnnouncement(User $creator, array $data): Announcement
    {
        $announcement = Announcement::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'created_by' => $creator->id,
            'priority' => $data['priority'] ?? Announcement::PRIORITY_MEDIUM,
            'target_type' => $data['target_type'] ?? Announcement::TARGET_ALL,
            'is_pinned' => $data['is_pinned'] ?? false,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        // Attach targets based on target_type
        $this->attachTargets($announcement, $data);

        ActivityLog::log(ActivityLog::ACTION_CREATE, $announcement);

        // Create notifications for targeted users
        $this->notifyTargetedUsers($announcement);

        return $announcement->fresh(['creator', 'departments', 'users']);
    }

    /**
     * Update an existing announcement.
     */
    public function updateAnnouncement(Announcement $announcement, array $data): Announcement
    {
        $oldValues = $announcement->only([
            'title', 'content', 'priority', 'target_type', 'is_pinned', 'starts_at', 'expires_at'
        ]);

        $announcement->update([
            'title' => $data['title'] ?? $announcement->title,
            'content' => $data['content'] ?? $announcement->content,
            'priority' => $data['priority'] ?? $announcement->priority,
            'target_type' => $data['target_type'] ?? $announcement->target_type,
            'is_pinned' => $data['is_pinned'] ?? $announcement->is_pinned,
            'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $announcement->starts_at,
            'expires_at' => array_key_exists('expires_at', $data) ? $data['expires_at'] : $announcement->expires_at,
        ]);

        // Update targets if target_type changed or new targets provided
        if (isset($data['target_type']) || isset($data['user_ids']) || isset($data['department_ids'])) {
            $this->updateTargets($announcement, $data);
        }

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $announcement,
            $oldValues,
            $announcement->only(['title', 'content', 'priority', 'target_type', 'is_pinned', 'starts_at', 'expires_at'])
        );

        return $announcement->fresh(['creator', 'departments', 'users']);
    }

    /**
     * Soft delete an announcement.
     */
    public function deleteAnnouncement(Announcement $announcement): bool
    {
        ActivityLog::log(ActivityLog::ACTION_DELETE, $announcement);

        return $announcement->delete();
    }

    /**
     * Attach targets to an announcement based on target_type.
     */
    private function attachTargets(Announcement $announcement, array $data): void
    {
        switch ($announcement->target_type) {
            case Announcement::TARGET_USERS:
                if (!empty($data['user_ids'])) {
                    $announcement->users()->attach($data['user_ids']);
                }
                break;

            case Announcement::TARGET_DEPARTMENTS:
                if (!empty($data['department_ids'])) {
                    $announcement->departments()->attach($data['department_ids']);
                }
                break;

            case Announcement::TARGET_ALL:
            case Announcement::TARGET_ROLES:
                // No additional pivot data needed for 'all' or 'roles'
                break;
        }
    }

    /**
     * Update targets for an announcement.
     */
    private function updateTargets(Announcement $announcement, array $data): void
    {
        // Clear existing targets
        $announcement->users()->detach();
        $announcement->departments()->detach();

        // Re-attach based on new data
        $this->attachTargets($announcement, $data);
    }

    /**
     * Get all users targeted by an announcement.
     */
    public function getTargetedUsers(Announcement $announcement): Collection
    {
        switch ($announcement->target_type) {
            case Announcement::TARGET_ALL:
                return User::active()->get();

            case Announcement::TARGET_USERS:
                return $announcement->users()->active()->get();

            case Announcement::TARGET_DEPARTMENTS:
                $departmentIds = $announcement->departments()->pluck('departments.id');
                return User::active()
                    ->whereIn('department_id', $departmentIds)
                    ->get();

            case Announcement::TARGET_ROLES:
                // For role-based targeting, we'd need to implement role storage
                // For now, return empty collection
                return collect();

            default:
                return collect();
        }
    }

    /**
     * Create notifications for all targeted users.
     */
    private function notifyTargetedUsers(Announcement $announcement): void
    {
        // Skip if announcement is scheduled for the future
        if ($announcement->isScheduled()) {
            return;
        }

        $targetedUsers = $this->getTargetedUsers($announcement);

        // Use NotificationService to handle in-app, email, and KingsChat notifications
        $this->notificationService->notifyAnnouncement($announcement, $targetedUsers);
    }

    /**
     * Check if announcement is read by user.
     */
    public function isReadByUser(Announcement $announcement, User $user): bool
    {
        return $announcement->isReadBy($user);
    }

    /**
     * Get read status for announcement-user combination.
     */
    public function getReadAt(Announcement $announcement, User $user): ?string
    {
        $pivot = $announcement->users()
            ->wherePivot('user_id', $user->id)
            ->first();

        return $pivot?->pivot?->read_at?->toIso8601String();
    }
}
