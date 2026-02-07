<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    /**
     * All authenticated users can view announcements targeted to them.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * User can view if announcement is targeted to them.
     */
    public function view(User $user, Announcement $announcement): bool
    {
        // Admins can view any announcement
        if ($this->isAnnouncementAdmin($user)) {
            return true;
        }

        // Check if announcement is targeted to this user
        return $this->isTargetedToUser($announcement, $user);
    }

    /**
     * Only admins, super_admin, and head_of_operations can create announcements.
     */
    public function create(User $user): bool
    {
        return $this->isAnnouncementAdmin($user);
    }

    /**
     * Only admins, super_admin, and head_of_operations can update announcements.
     */
    public function update(User $user, Announcement $announcement): bool
    {
        return $this->isAnnouncementAdmin($user);
    }

    /**
     * Only admins, super_admin, and head_of_operations can delete announcements.
     */
    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->isAnnouncementAdmin($user);
    }

    /**
     * Check if user has admin privileges for announcements.
     */
    private function isAnnouncementAdmin(User $user): bool
    {
        return $user->isAdmin() || $user->hasRole('head_of_operations');
    }

    /**
     * Check if announcement is targeted to a specific user.
     */
    private function isTargetedToUser(Announcement $announcement, User $user): bool
    {
        // Target all users
        if ($announcement->target_type === Announcement::TARGET_ALL) {
            return true;
        }

        // Target specific users
        if ($announcement->target_type === Announcement::TARGET_USERS) {
            return $announcement->users()->where('user_id', $user->id)->exists();
        }

        // Target departments
        if ($announcement->target_type === Announcement::TARGET_DEPARTMENTS) {
            if (!$user->department_id) {
                return false;
            }
            return $announcement->departments()->where('department_id', $user->department_id)->exists();
        }

        // Target roles
        if ($announcement->target_type === Announcement::TARGET_ROLES) {
            // Check if user has any of the targeted roles
            $targetedRoles = $announcement->users()->pluck('role')->filter()->toArray();
            foreach ($targetedRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }
}
