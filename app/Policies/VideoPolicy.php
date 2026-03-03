<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;

class VideoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Video $video): bool
    {
        if ($this->isVideoAdmin($user)) {
            return true;
        }

        return $this->isTargetedToUser($video, $user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Video $video): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Video $video): bool
    {
        return $user->isAdmin();
    }

    public function viewAttendance(User $user): bool
    {
        return $user->isAdmin() || $user->hasRole('head_of_operations') || $user->isHOD();
    }

    private function isVideoAdmin(User $user): bool
    {
        return $user->isAdmin() || $user->hasRole('head_of_operations');
    }

    private function isTargetedToUser(Video $video, User $user): bool
    {
        if ($video->target_type === Video::TARGET_ALL) {
            return true;
        }

        if ($video->target_type === Video::TARGET_USERS) {
            return $video->users()->where('user_id', $user->id)->exists();
        }

        if ($video->target_type === Video::TARGET_DEPARTMENTS) {
            if (!$user->department_id) {
                return false;
            }
            return $video->departments()->where('department_id', $user->department_id)->exists();
        }

        return false;
    }
}
