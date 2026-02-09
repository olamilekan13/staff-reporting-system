<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Comment;
use App\Models\Department;
use App\Models\Notification;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Observers\UserObserver;
use App\Policies\AnnouncementPolicy;
use App\Policies\CommentPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\NotificationPreferencePolicy;
use App\Policies\ProposalPolicy;
use App\Policies\ReportPolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configurePolicies();
        $this->registerObservers();
        $this->configureMailFromDatabase();

        View::composer('layouts.app', \App\View\Composers\AppLayoutComposer::class);

        Event::listen(
            \Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent::class,
            \App\Listeners\ConvertOfficeDocumentToPdf::class
        );
    }

    /**
     * Configure authorization policies.
     */
    protected function configurePolicies(): void
    {
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
        Gate::policy(Proposal::class, ProposalPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(UserNotificationPreference::class, NotificationPreferencePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(SiteSetting::class, SettingPolicy::class);
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        User::observe(UserObserver::class);
    }

    /**
     * Override mail configuration from database settings.
     */
    protected function configureMailFromDatabase(): void
    {
        try {
            $mailer = SiteSetting::get('mail_mailer');
        } catch (\Exception $e) {
            return;
        }

        if (!$mailer) {
            return;
        }

        config()->set('mail.default', $mailer);

        if ($mailer === 'smtp') {
            $password = SiteSetting::get('smtp_password');

            config()->set('mail.mailers.smtp.host', SiteSetting::get('smtp_host', '127.0.0.1'));
            config()->set('mail.mailers.smtp.port', (int) SiteSetting::get('smtp_port', 587));
            config()->set('mail.mailers.smtp.username', SiteSetting::get('smtp_username'));
            config()->set('mail.mailers.smtp.password', $password ? decrypt($password) : null);
            config()->set('mail.mailers.smtp.scheme', SiteSetting::get('smtp_encryption') ?: null);
        }

        $fromAddress = SiteSetting::get('mail_from_address');
        $fromName = SiteSetting::get('mail_from_name');

        if ($fromAddress) {
            config()->set('mail.from.address', $fromAddress);
        }
        if ($fromName) {
            config()->set('mail.from.name', $fromName);
        }
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('api.rate_limits.default.requests', 60))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(config('api.rate_limits.auth.requests', 5))
                ->by($request->ip());
        });
    }
}
