<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'email_enabled',
        'notification_types',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'notification_types' => 'array',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper Methods
    public function isTypeEnabled(string $type): bool
    {
        if (!$this->notification_types) {
            return true; // Default to enabled
        }

        return $this->notification_types[$type] ?? true;
    }

    public function enableType(string $type): void
    {
        $types = $this->notification_types ?? [];
        $types[$type] = true;
        $this->notification_types = $types;
    }

    public function disableType(string $type): void
    {
        $types = $this->notification_types ?? [];
        $types[$type] = false;
        $this->notification_types = $types;
    }

    public function getEnabledTypes(): array
    {
        if (!$this->notification_types) {
            return ['comment', 'report_status', 'proposal_status', 'announcement', 'system'];
        }

        return array_keys(array_filter($this->notification_types, fn($enabled) => $enabled === true));
    }
}
