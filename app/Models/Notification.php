<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'notifiable_type',
        'notifiable_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    // Constants
    const TYPE_COMMENT = 'comment';
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_REPORT_STATUS = 'report_status';
    const TYPE_PROPOSAL_STATUS = 'proposal_status';
    const TYPE_SYSTEM = 'system';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Helper Methods
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    public function getIcon(): string
    {
        return match ($this->type) {
            self::TYPE_COMMENT => 'chat-bubble-left-ellipsis',
            self::TYPE_ANNOUNCEMENT => 'megaphone',
            self::TYPE_REPORT_STATUS => 'document-text',
            self::TYPE_PROPOSAL_STATUS => 'light-bulb',
            self::TYPE_SYSTEM => 'cog',
            default => 'bell',
        };
    }

    public function getColor(): string
    {
        return match ($this->type) {
            self::TYPE_COMMENT => 'blue',
            self::TYPE_ANNOUNCEMENT => 'purple',
            self::TYPE_REPORT_STATUS => 'green',
            self::TYPE_PROPOSAL_STATUS => 'yellow',
            self::TYPE_SYSTEM => 'gray',
            default => 'gray',
        };
    }

    public function getLink(): ?string
    {
        $data = $this->data ?? [];
        $user = $this->user ?? auth()->user();

        // Determine the route prefix based on user role
        $routePrefix = 'staff';
        if ($user) {
            if ($user->hasRole(['super_admin', 'admin'])) {
                $routePrefix = 'admin';
            } elseif ($user->hasRole('hod') || $user->isHOD()) {
                $routePrefix = 'hod';
            }
        }

        return match ($this->type) {
            self::TYPE_COMMENT => isset($data['report_id'])
                ? route("{$routePrefix}.reports.show", $data['report_id'])
                : (isset($data['proposal_id']) ? route("{$routePrefix}.proposals.show", $data['proposal_id']) : null),
            self::TYPE_REPORT_STATUS => isset($data['report_id'])
                ? route("{$routePrefix}.reports.show", $data['report_id'])
                : null,
            self::TYPE_PROPOSAL_STATUS => isset($data['proposal_id'])
                ? route("{$routePrefix}.proposals.show", $data['proposal_id'])
                : null,
            self::TYPE_ANNOUNCEMENT => isset($data['announcement_id'])
                ? route('announcements.show', $data['announcement_id'])
                : null,
            default => null,
        };
    }

    // Static methods for creating notifications
    public static function createForComment(Comment $comment, User $recipient): self
    {
        $commentable = $comment->commentable;
        $type = $commentable instanceof Report ? 'report' : 'proposal';

        return self::create([
            'user_id' => $recipient->id,
            'type' => self::TYPE_COMMENT,
            'title' => 'New Comment',
            'message' => "{$comment->user->full_name} commented on your {$type}: \"{$commentable->title}\"",
            'data' => [
                'comment_id' => $comment->id,
                "{$type}_id" => $commentable->id,
            ],
            'notifiable_type' => get_class($comment),
            'notifiable_id' => $comment->id,
        ]);
    }

    public static function createForReportStatus(Report $report): self
    {
        return self::create([
            'user_id' => $report->user_id,
            'type' => self::TYPE_REPORT_STATUS,
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

    public static function createForProposalStatus(Proposal $proposal): self
    {
        return self::create([
            'user_id' => $proposal->user_id,
            'type' => self::TYPE_PROPOSAL_STATUS,
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

    public static function createForAnnouncement(Announcement $announcement, User $recipient): self
    {
        return self::create([
            'user_id' => $recipient->id,
            'type' => self::TYPE_ANNOUNCEMENT,
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
}
