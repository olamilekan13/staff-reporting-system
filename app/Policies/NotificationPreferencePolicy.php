<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotificationPreference;

class NotificationPreferencePolicy
{
    /**
     * Determine if the user can view their notification preferences.
     * Users can always view their own preferences.
     */
    public function view(User $user, UserNotificationPreference $preference): bool
    {
        return $preference->user_id === $user->id;
    }

    /**
     * Determine if the user can update their notification preferences.
     * Users can only update their own preferences.
     */
    public function update(User $user, UserNotificationPreference $preference): bool
    {
        return $preference->user_id === $user->id;
    }
}
