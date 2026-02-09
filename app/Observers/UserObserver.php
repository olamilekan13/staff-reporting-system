<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserNotificationPreference;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Create default notification preferences for new user
        UserNotificationPreference::create([
            'user_id' => $user->id,
            'email_enabled' => true,
            'notification_types' => [
                'comment' => true,
                'report_status' => true,
                'proposal_status' => true,
                'announcement' => true,
                'system' => true,
            ],
        ]);
    }
}
