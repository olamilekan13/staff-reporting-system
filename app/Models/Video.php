<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Video extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'video_category_id',
        'source_type',
        'source_url',
        'duration_seconds',
        'status',
        'target_type',
        'publish_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    // Source type constants
    const SOURCE_UPLOAD = 'upload';
    const SOURCE_YOUTUBE = 'youtube';
    const SOURCE_VIMEO = 'vimeo';
    const SOURCE_M3U8 = 'm3u8';
    const SOURCE_EMBED = 'embed';

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    // Target constants (matching Announcement pattern)
    const TARGET_ALL = 'all';
    const TARGET_DEPARTMENTS = 'departments';
    const TARGET_USERS = 'users';

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(VideoCategory::class, 'video_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'video_department')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'video_user')
            ->withTimestamps();
    }

    public function watchLogs(): HasMany
    {
        return $this->hasMany(VideoWatchLog::class, 'watchable_id')
            ->where('watchable_type', self::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            });
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('target_type', self::TARGET_ALL);

            $q->orWhere(function ($subQ) use ($user) {
                $subQ->where('target_type', self::TARGET_USERS)
                    ->whereHas('users', function ($userQ) use ($user) {
                        $userQ->where('user_id', $user->id);
                    });
            });

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

    // Media Library
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('video_file')
            ->singleFile()
            ->acceptsMimeTypes([
                'video/mp4', 'video/webm', 'video/quicktime',
            ]);

        $this->addMediaCollection('video_thumbnail')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/webp',
            ]);
    }

    // Helper Methods
    public function isPublished(): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        return !$this->publish_at || $this->publish_at->lte(now());
    }

    public function isYouTube(): bool
    {
        return str_contains((string) $this->source_url, 'youtube.com')
            || str_contains((string) $this->source_url, 'youtu.be');
    }

    public function isVimeo(): bool
    {
        return str_contains((string) $this->source_url, 'vimeo.com');
    }

    public function getYouTubeEmbedUrl(): ?string
    {
        if (!$this->source_url || !$this->isYouTube()) {
            return null;
        }

        $parsed = parse_url($this->source_url);

        if (isset($parsed['host']) && str_contains($parsed['host'], 'youtu.be')) {
            $videoId = ltrim($parsed['path'] ?? '', '/');
        } else {
            parse_str($parsed['query'] ?? '', $query);
            $videoId = $query['v'] ?? null;
        }

        if (!$videoId) {
            return null;
        }

        return 'https://www.youtube.com/embed/' . urlencode($videoId) . '?rel=0&modestbranding=1';
    }

    public function getVimeoEmbedUrl(): ?string
    {
        if (!$this->source_url || !$this->isVimeo()) {
            return null;
        }

        $parsed = parse_url($this->source_url);
        $videoId = ltrim($parsed['path'] ?? '', '/');

        if (!$videoId || !ctype_digit($videoId)) {
            return null;
        }

        return 'https://player.vimeo.com/video/' . $videoId;
    }

    public function getVideoUrl(): ?string
    {
        if ($this->source_type === self::SOURCE_UPLOAD) {
            return $this->getFirstMediaUrl('video_file') ?: null;
        }

        return $this->source_url;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->getFirstMediaUrl('video_thumbnail') ?: null;
    }

    public function getFormattedDuration(): string
    {
        if (!$this->duration_seconds) {
            return '--:--';
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getSourceTypeBadge(): array
    {
        return match ($this->source_type) {
            self::SOURCE_UPLOAD => ['label' => 'Upload', 'class' => 'bg-green-100 text-green-700'],
            self::SOURCE_YOUTUBE => ['label' => 'YouTube', 'class' => 'bg-red-100 text-red-700'],
            self::SOURCE_VIMEO => ['label' => 'Vimeo', 'class' => 'bg-blue-100 text-blue-700'],
            self::SOURCE_M3U8 => ['label' => 'HLS', 'class' => 'bg-purple-100 text-purple-700'],
            self::SOURCE_EMBED => ['label' => 'Embed', 'class' => 'bg-orange-100 text-orange-700'],
            default => ['label' => ucfirst($this->source_type), 'class' => 'bg-gray-100 text-gray-700'],
        };
    }

    public function getStatusBadge(): array
    {
        return match ($this->status) {
            self::STATUS_DRAFT => ['label' => 'Draft', 'type' => 'warning'],
            self::STATUS_PUBLISHED => ['label' => 'Published', 'type' => 'success'],
            self::STATUS_ARCHIVED => ['label' => 'Archived', 'type' => 'danger'],
            default => ['label' => ucfirst($this->status), 'type' => 'secondary'],
        };
    }
}
