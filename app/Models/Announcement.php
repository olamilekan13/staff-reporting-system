<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Announcement extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // When an announcement is being deleted, delete all related notifications
        static::deleting(function ($announcement) {
            // Delete all notifications related to this announcement
            $announcement->notifications()->delete();
        });
    }

    protected $fillable = [
        'title',
        'content',
        'announcement_type',
        'media_url',
        'media_title',
        'created_by',
        'priority',
        'target_type',
        'is_pinned',
        'starts_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // Constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const TARGET_ALL = 'all';
    const TARGET_DEPARTMENTS = 'departments';
    const TARGET_USERS = 'users';
    const TARGET_ROLES = 'roles';

    const TYPE_TEXT = 'text';
    const TYPE_VIDEO_UPLOAD = 'video_upload';
    const TYPE_AUDIO_UPLOAD = 'audio_upload';
    const TYPE_YOUTUBE = 'youtube';
    const TYPE_VIMEO = 'vimeo';
    const TYPE_LIVESTREAM = 'livestream';

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'announcement_user')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'announcement_department')
            ->withTimestamps();
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now());
        });
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // All users
            $q->where('target_type', self::TARGET_ALL);

            // Specific users
            $q->orWhere(function ($subQ) use ($user) {
                $subQ->where('target_type', self::TARGET_USERS)
                    ->whereHas('users', function ($userQ) use ($user) {
                        $userQ->where('user_id', $user->id);
                    });
            });

            // User's department
            if ($user->department_id) {
                $q->orWhere(function ($subQ) use ($user) {
                    $subQ->where('target_type', self::TARGET_DEPARTMENTS)
                        ->whereHas('departments', function ($deptQ) use ($user) {
                            $deptQ->where('department_id', $user->department_id);
                        });
                });
            }
        });
    }

    // Helper Methods
    public function getPriorityBadgeClass(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'badge-secondary',
            self::PRIORITY_MEDIUM => 'badge-primary',
            self::PRIORITY_HIGH => 'badge-warning',
            self::PRIORITY_URGENT => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    public function isActive(): bool
    {
        $now = now();

        $hasStarted = !$this->starts_at || $this->starts_at->lte($now);
        $notExpired = !$this->expires_at || $this->expires_at->gte($now);

        return $hasStarted && $notExpired;
    }

    public function markAsReadBy(User $user): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => ['read_at' => now()]
        ]);
    }

    public function isReadBy(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivotNotNull('read_at')
            ->exists();
    }

    // Media Helper Methods

    public function isYouTube(): bool
    {
        return str_contains((string) $this->media_url, 'youtube.com')
            || str_contains((string) $this->media_url, 'youtu.be');
    }

    public function isVimeo(): bool
    {
        return str_contains((string) $this->media_url, 'vimeo.com');
    }

    public function isLivestream(): bool
    {
        return $this->announcement_type === self::TYPE_LIVESTREAM;
    }

    public function hasMediaContent(): bool
    {
        return $this->announcement_type !== self::TYPE_TEXT;
    }

    public function getYouTubeEmbedUrl(): ?string
    {
        if (! $this->media_url || ! $this->isYouTube()) {
            return null;
        }

        $parsed = parse_url($this->media_url);

        // youtu.be/VIDEO_ID
        if (isset($parsed['host']) && str_contains($parsed['host'], 'youtu.be')) {
            $videoId = ltrim($parsed['path'] ?? '', '/');
        } else {
            // youtube.com/watch?v=VIDEO_ID
            parse_str($parsed['query'] ?? '', $query);
            $videoId = $query['v'] ?? null;
        }

        if (! $videoId) {
            return null;
        }

        return 'https://www.youtube.com/embed/' . $videoId . '?rel=0&modestbranding=1';
    }

    public function getVimeoEmbedUrl(): ?string
    {
        if (! $this->media_url || ! $this->isVimeo()) {
            return null;
        }

        $parsed = parse_url($this->media_url);
        $videoId = ltrim($parsed['path'] ?? '', '/');

        // Accept only numeric Vimeo IDs (e.g. vimeo.com/123456789)
        if (! $videoId || ! ctype_digit($videoId)) {
            return null;
        }

        return 'https://player.vimeo.com/video/' . $videoId;
    }

    public function getUploadedMediaUrl(): ?string
    {
        return $this->getFirstMediaUrl('announcement_media') ?: null;
    }

    // Media Library
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('announcement_media')
            ->singleFile();
    }

    public function getMediaType(): string
    {
        return match ($this->announcement_type) {
            self::TYPE_VIDEO_UPLOAD, self::TYPE_YOUTUBE, self::TYPE_VIMEO => 'video',
            self::TYPE_AUDIO_UPLOAD => 'audio',
            self::TYPE_LIVESTREAM => 'livestream',
            default => 'text',
        };
    }
}
