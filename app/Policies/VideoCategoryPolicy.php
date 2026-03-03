<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VideoCategory;

class VideoCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, VideoCategory $category): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, VideoCategory $category): bool
    {
        return $user->isAdmin();
    }
}
