<?php

namespace App\Services;

use App\Mail\NewAnnouncementMail;
use App\Mail\NewCommentForAdminMail;
use App\Mail\NewCommentMail;
use App\Mail\NewProposalMail;
use App\Mail\NewReportMail;
use App\Mail\ProposalStatusChangedMail;
use App\Mail\ReportStatusChangedMail;
use App\Models\Announcement;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Get notifications for a user with optional filters.
     */
    public function getNotificationsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::query()
            ->where('user_id', $user->id)
            ->latestFirst();

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['unread']) && $filters['unread']) {
            $query->unread();
        }

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->paginate($perPage);
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->count();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a notification.
     */
    public function deleteNotification(Notification $notification): bool
    {
        return $notification->delete();
    }

    /**
     * Notify about a new comment on a report or proposal.
     */
    public function notifyComment(Comment $comment): void
    {
        $owner = $comment->getOwner();

        if (!$owner || $owner->id === $comment->user_id) {
            return;
        }

        // Check if user wants comment notifications
        if (!$this->isNotificationTypeEnabled($owner, Notification::TYPE_COMMENT)) {
            return;
        }

        $this->createCommentNotification($comment, $owner);

        if ($comment->parent_id) {
            $this->notifyParentCommentAuthor($comment, $owner);
        }
    }

    /**
     * Notify report owner about status change.
     */
    public function notifyReportStatus(Report $report): void
    {
        $owner = $report->user;

        if (!$owner) {
            return;
        }

        // Check if user wants report_status notifications
        if (!$this->isNotificationTypeEnabled($owner, Notification::TYPE_REPORT_STATUS)) {
            return;
        }

        $this->createReportStatusNotification($report);

        if ($this->isEmailEnabled($owner) && $owner->email) {
            $this->sendEmail($owner, new ReportStatusChangedMail($report));
        }

        if ($this->isKingsChatEnabled($owner)) {
            $message = "Your report \"{$report->title}\" status has been updated to: {$report->status}";
            $this->sendKingsChat($owner, $message);
        }
    }

    /**
     * Notify proposal owner about status change.
     */
    public function notifyProposalStatus(Proposal $proposal): void
    {
        $owner = $proposal->user;

        if (!$owner) {
            return;
        }

        // Check if user wants proposal_status notifications
        if (!$this->isNotificationTypeEnabled($owner, Notification::TYPE_PROPOSAL_STATUS)) {
            return;
        }

        $this->createProposalStatusNotification($proposal);

        if ($this->isEmailEnabled($owner) && $owner->email) {
            $this->sendEmail($owner, new ProposalStatusChangedMail($proposal));
        }

        if ($this->isKingsChatEnabled($owner)) {
            $status = str_replace('_', ' ', $proposal->status);
            $message = "Your proposal \"{$proposal->title}\" status has been updated to: {$status}";
            $this->sendKingsChat($owner, $message);
        }
    }

    /**
     * Notify users about a new announcement.
     */
    public function notifyAnnouncement(Announcement $announcement, Collection $recipients): void
    {
        $recipients = $recipients->filter(fn ($user) => $user->id !== $announcement->created_by);

        foreach ($recipients as $recipient) {
            // Check if user wants announcement notifications
            if (!$this->isNotificationTypeEnabled($recipient, Notification::TYPE_ANNOUNCEMENT)) {
                continue;
            }

            $this->createAnnouncementNotification($announcement, $recipient);

            if ($this->isEmailEnabled($recipient) && $recipient->email) {
                $this->sendEmail($recipient, new NewAnnouncementMail($announcement, $recipient));
            }

            if ($this->isKingsChatEnabled($recipient)) {
                $priority = $announcement->priority === Announcement::PRIORITY_URGENT ? '[URGENT] ' : '';
                $message = "{$priority}New Announcement: {$announcement->title}";
                $this->sendKingsChat($recipient, $message);
            }
        }
    }

    /**
     * Notify reviewers about a submitted report.
     */
    public function notifyReportSubmission(Report $report, Collection $reviewers): void
    {
        $reviewers = $reviewers->filter(fn ($user) => $user->id !== $report->user_id);

        foreach ($reviewers as $reviewer) {
            Notification::create([
                'user_id' => $reviewer->id,
                'type' => Notification::TYPE_SYSTEM,
                'title' => 'New Report Submitted',
                'message' => "{$report->user->full_name} submitted a new {$report->report_category} report: \"{$report->title}\"",
                'data' => [
                    'report_id' => $report->id,
                    'report_title' => $report->title,
                ],
                'notifiable_type' => Report::class,
                'notifiable_id' => $report->id,
            ]);
        }
    }

    /**
     * Send email notification.
     */
    public function sendEmail(User $user, Mailable $mailable): void
    {
        if (!$user->email) {
            Log::warning("Cannot send email notification to user {$user->id}: no email address");
            return;
        }

        try {
            Mail::to($user->email)->send($mailable);
        } catch (\Exception $e) {
            Log::error("Failed to send email for user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Send KingsChat notification.
     *
     * DEFERRED: KingsChat integration postponed pending server-side API availability.
     * Current decision: Focus on email notifications only.
     *
     * Future implementation options:
     * - KingsChat server-side API (if/when released)
     * - Firebase Cloud Messaging for mobile push
     * - Alternative messaging platforms (Slack, WhatsApp Business, etc.)
     *
     * @param User $user
     * @param string $message
     * @return void
     */
    public function sendKingsChat(User $user, string $message): void
    {
        // Integration deferred - method kept for future implementation
        return;
    }

    /**
     * Check if email notifications are enabled for a user.
     */
    private function isEmailEnabled(User $user): bool
    {
        // Check global site setting first
        if (!SiteSetting::get('enable_email_notifications', true)) {
            return false;
        }

        // Check user preference
        $preferences = $user->notificationPreferences;
        if (!$preferences || !$preferences->email_enabled) {
            return false;
        }

        return true;
    }

    /**
     * Check if KingsChat notifications are enabled for a user.
     */
    private function isKingsChatEnabled(User $user): bool
    {
        // KingsChat integration deferred - return false for now
        // Future: Implement when server-side API becomes available
        return false;
    }

    /**
     * Check if a specific notification type is enabled for a user.
     */
    private function isNotificationTypeEnabled(User $user, string $type): bool
    {
        $preferences = $user->notificationPreferences;

        if (!$preferences) {
            return true; // Default to enabled if no preferences set
        }

        return $preferences->isTypeEnabled($type);
    }

    /**
     * Create in-app notification for a comment and send email.
     */
    private function createCommentNotification(Comment $comment, User $recipient): void
    {
        $commentable = $comment->commentable;
        $type = $commentable instanceof Report ? 'report' : 'proposal';

        Notification::create([
            'user_id' => $recipient->id,
            'type' => Notification::TYPE_COMMENT,
            'title' => 'New Comment',
            'message' => "{$comment->user->full_name} commented on your {$type}: \"{$commentable->title}\"",
            'data' => [
                'comment_id' => $comment->id,
                "{$type}_id" => $commentable->id,
            ],
            'notifiable_type' => get_class($comment),
            'notifiable_id' => $comment->id,
        ]);

        if ($this->isEmailEnabled($recipient) && $recipient->email) {
            $this->sendEmail($recipient, new NewCommentMail($comment, $recipient));
        }

        if ($this->isKingsChatEnabled($recipient)) {
            $message = "{$comment->user->full_name} commented on your {$type}: \"{$commentable->title}\"";
            $this->sendKingsChat($recipient, $message);
        }
    }

    /**
     * Notify parent comment author if this is a reply.
     */
    private function notifyParentCommentAuthor(Comment $comment, ?User $resourceOwner): void
    {
        $parentComment = $comment->parent;

        if (!$parentComment) {
            return;
        }

        $parentAuthor = $parentComment->user;

        if (!$parentAuthor) {
            return;
        }

        if ($parentAuthor->id === $comment->user_id) {
            return;
        }

        if ($resourceOwner && $parentAuthor->id === $resourceOwner->id) {
            return;
        }

        $commentable = $comment->commentable;
        $type = $commentable instanceof Report ? 'report' : 'proposal';

        Notification::create([
            'user_id' => $parentAuthor->id,
            'type' => Notification::TYPE_COMMENT,
            'title' => 'New Reply to Your Comment',
            'message' => "{$comment->user->full_name} replied to your comment on \"{$commentable->title}\"",
            'data' => [
                'comment_id' => $comment->id,
                'parent_comment_id' => $parentComment->id,
                "{$type}_id" => $commentable->id,
            ],
            'notifiable_type' => get_class($comment),
            'notifiable_id' => $comment->id,
        ]);

        if ($this->isEmailEnabled($parentAuthor) && $parentAuthor->email) {
            $this->sendEmail($parentAuthor, new NewCommentMail($comment, $parentAuthor));
        }

        if ($this->isKingsChatEnabled($parentAuthor)) {
            $message = "{$comment->user->full_name} replied to your comment on \"{$commentable->title}\"";
            $this->sendKingsChat($parentAuthor, $message);
        }
    }

    /**
     * Create in-app notification for report status change.
     */
    private function createReportStatusNotification(Report $report): void
    {
        Notification::create([
            'user_id' => $report->user_id,
            'type' => Notification::TYPE_REPORT_STATUS,
            'title' => 'Report Status Updated',
            'message' => "Your report \"{$report->title}\" status has been updated to: {$report->status}",
            'data' => [
                'report_id' => $report->id,
                'status' => $report->status,
            ],
            'notifiable_type' => Report::class,
            'notifiable_id' => $report->id,
        ]);
    }

    /**
     * Create in-app notification for proposal status change.
     */
    private function createProposalStatusNotification(Proposal $proposal): void
    {
        Notification::create([
            'user_id' => $proposal->user_id,
            'type' => Notification::TYPE_PROPOSAL_STATUS,
            'title' => 'Proposal Status Updated',
            'message' => "Your proposal \"{$proposal->title}\" status has been updated to: {$proposal->status}",
            'data' => [
                'proposal_id' => $proposal->id,
                'status' => $proposal->status,
            ],
            'notifiable_type' => Proposal::class,
            'notifiable_id' => $proposal->id,
        ]);
    }

    /**
     * Create in-app notification for announcement.
     */
    private function createAnnouncementNotification(Announcement $announcement, User $recipient): void
    {
        Notification::create([
            'user_id' => $recipient->id,
            'type' => Notification::TYPE_ANNOUNCEMENT,
            'title' => 'New Announcement',
            'message' => $announcement->title,
            'data' => [
                'announcement_id' => $announcement->id,
                'priority' => $announcement->priority,
            ],
            'notifiable_type' => Announcement::class,
            'notifiable_id' => $announcement->id,
        ]);
    }

    /**
     * Notify super admins about a newly created report.
     */
    public function notifyNewReport(Report $report): void
    {
        $superAdmins = User::role('super_admin')->active()->get();

        foreach ($superAdmins as $admin) {
            if ($admin->email) {
                $this->sendEmail($admin, new NewReportMail($report));
            }
        }
    }

    /**
     * Notify super admins about a newly created proposal.
     */
    public function notifyNewProposal(Proposal $proposal): void
    {
        $superAdmins = User::role('super_admin')->active()->get();

        foreach ($superAdmins as $admin) {
            if ($admin->email) {
                $this->sendEmail($admin, new NewProposalMail($proposal));
            }
        }
    }

    /**
     * Notify super admins about a newly created comment.
     */
    public function notifyNewComment(Comment $comment): void
    {
        $superAdmins = User::role('super_admin')->active()->get();

        foreach ($superAdmins as $admin) {
            if ($admin->email) {
                $this->sendEmail($admin, new NewCommentForAdminMail($comment));
            }
        }
    }
}
