<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    /**
     * Admins and super admins can view settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only super admins can manage (update) settings.
     */
    public function manage(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
