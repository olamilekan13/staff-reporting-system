<?php

namespace App\View\Composers;

use App\Helpers\ColorHelper;
use App\Models\Notification;
use App\Models\SiteSetting;
use Illuminate\View\View;

class AppLayoutComposer
{
    public function compose(View $view): void
    {
        $primaryColor = SiteSetting::get('primary_color', '#3b82f6');
        $secondaryColor = SiteSetting::get('secondary_color', '#64748b');

        $siteSettings = [
            'site_name' => SiteSetting::get('site_name', 'Staff Reporting Management'),
            'site_logo' => SiteSetting::get('site_logo'),
            'site_favicon' => SiteSetting::get('site_favicon'),
            'primary_color' => $primaryColor,
            'secondary_color' => $secondaryColor,
            'custom_css' => SiteSetting::get('custom_css', ''),
            'primary_color_shades' => ColorHelper::generateShades($primaryColor),
            'secondary_color_shades' => ColorHelper::generateShades($secondaryColor),
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
