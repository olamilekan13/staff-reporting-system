<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoWatchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'watchable_type',
        'watchable_id',
        'session_id',
        'started_at',
        'last_heartbeat_at',
        'ended_at',
        'duration_seconds',
        'completed',
        'source',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'ended_at' => 'datetime',
            'completed' => 'boolean',
            'duration_seconds' => 'integer',
        ];
    }

    const SOURCE_VOD = 'vod';
    const SOURCE_LIVESTREAM = 'livestream';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'watchable_id');
    }

    // Scopes
    public function scopeForVideo($query, int $videoId)
    {
        return $query->where('watchable_type', Video::class)
            ->where('watchable_id', $videoId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForLivestream($query)
    {
        return $query->where('source', self::SOURCE_LIVESTREAM);
    }

    public function scopeForVod($query)
    {
        return $query->where('source', self::SOURCE_VOD);
    }

    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        if ($from) {
            $query->where('started_at', '>=', $from);
        }
        if ($to) {
            $query->where('started_at', '<=', $to);
        }

        return $query;
    }

    // Helpers
    public function getFormattedDuration(): string
    {
        $seconds = $this->duration_seconds;

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
        }

        return sprintf('%dm %ds', $minutes, $secs);
    }
}
