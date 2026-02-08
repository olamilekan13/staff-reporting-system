<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

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
}
