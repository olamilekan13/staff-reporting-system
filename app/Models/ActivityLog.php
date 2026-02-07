<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    // Constants
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_VIEW = 'view';
    const ACTION_DOWNLOAD = 'download';
    const ACTION_UPLOAD = 'upload';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('model');
    }

    // Scopes
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByModel($query, string $modelType, ?int $modelId = null)
    {
        $query->where('model_type', $modelType);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function getActionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_LOGIN => 'Logged in',
            self::ACTION_LOGOUT => 'Logged out',
            self::ACTION_CREATE => 'Created',
            self::ACTION_UPDATE => 'Updated',
            self::ACTION_DELETE => 'Deleted',
            self::ACTION_VIEW => 'Viewed',
            self::ACTION_DOWNLOAD => 'Downloaded',
            self::ACTION_UPLOAD => 'Uploaded',
            default => ucfirst($this->action),
        };
    }

    public function getActionIcon(): string
    {
        return match ($this->action) {
            self::ACTION_LOGIN => 'arrow-right-on-rectangle',
            self::ACTION_LOGOUT => 'arrow-left-on-rectangle',
            self::ACTION_CREATE => 'plus-circle',
            self::ACTION_UPDATE => 'pencil-square',
            self::ACTION_DELETE => 'trash',
            self::ACTION_VIEW => 'eye',
            self::ACTION_DOWNLOAD => 'arrow-down-tray',
            self::ACTION_UPLOAD => 'arrow-up-tray',
            default => 'information-circle',
        };
    }

    public function getActionColor(): string
    {
        return match ($this->action) {
            self::ACTION_LOGIN => 'green',
            self::ACTION_LOGOUT => 'gray',
            self::ACTION_CREATE => 'blue',
            self::ACTION_UPDATE => 'yellow',
            self::ACTION_DELETE => 'red',
            self::ACTION_VIEW => 'gray',
            self::ACTION_DOWNLOAD => 'purple',
            self::ACTION_UPLOAD => 'indigo',
            default => 'gray',
        };
    }

    public function getModelName(): ?string
    {
        if (!$this->model_type) {
            return null;
        }

        return class_basename($this->model_type);
    }

    // Static method for logging
    public static function log(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
