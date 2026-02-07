<?php

namespace App\View\Composers;

use App\Models\Notification;
use App\Models\SiteSetting;
use Illuminate\View\View;

class AppLayoutComposer
{
    public function compose(View $view): void
    {
        $siteSettings = [
            'site_name' => SiteSetting::get('site_name', 'Staff Reporting Management'),
            'site_logo' => SiteSetting::get('site_logo'),
            'site_favicon' => SiteSetting::get('site_favicon'),
            'primary_color' => SiteSetting::get('primary_color', '#3b82f6'),
            'secondary_color' => SiteSetting::get('secondary_color', '#64748b'),
            'custom_css' => SiteSetting::get('custom_css', ''),
        ];

        $unreadNotificationCount = 0;

        if ($user = auth()->user()) {
            $unreadNotificationCount = Notification::where('user_id', $user->id)
                ->unread()
                ->count();
        }

        $view->with('siteSettings', $siteSettings);
        $view->with('unreadNotificationCount', $unreadNotificationCount);
    }
}
