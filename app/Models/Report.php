<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Report extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'department_id',
        'title',
        'description',
        'report_type',
        'report_category',
        'file_path',
        'file_name',
        'file_type',
        'mime_type',
        'file_size',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    // Constants
    const TYPE_PERSONAL = 'personal';
    const TYPE_DEPARTMENT = 'department';

    const CATEGORY_DAILY = 'daily';
    const CATEGORY_WEEKLY = 'weekly';
    const CATEGORY_MONTHLY = 'monthly';
    const CATEGORY_QUARTERLY = 'quarterly';
    const CATEGORY_ANNUAL = 'annual';

    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Scopes
    public function scopeByCategory($query, string $category)
    {
        return $query->where('report_category', $category);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    public function scopePersonal($query)
    {
        return $query->where('report_type', self::TYPE_PERSONAL);
    }

    public function scopeDepartmental($query)
    {
        return $query->where('report_type', self::TYPE_DEPARTMENT);
    }

    // Helper Methods
    public function getFileIcon(): string
    {
        $icons = [
            'pdf' => 'file-pdf',
            'doc' => 'file-word',
            'docx' => 'file-word',
            'xls' => 'file-excel',
            'xlsx' => 'file-excel',
            'ppt' => 'file-powerpoint',
            'pptx' => 'file-powerpoint',
            'jpg' => 'file-image',
            'jpeg' => 'file-image',
            'png' => 'file-image',
            'gif' => 'file-image',
            'mp4' => 'file-video',
            'mov' => 'file-video',
            'avi' => 'file-video',
        ];

        return $icons[$this->file_type] ?? 'file';
    }

    public function getFileColor(): string
    {
        $colors = [
            'pdf' => 'red',
            'doc' => 'blue',
            'docx' => 'blue',
            'xls' => 'green',
            'xlsx' => 'green',
            'ppt' => 'orange',
            'pptx' => 'orange',
            'jpg' => 'purple',
            'jpeg' => 'purple',
            'png' => 'purple',
            'gif' => 'purple',
            'mp4' => 'pink',
            'mov' => 'pink',
            'avi' => 'pink',
        ];

        return $colors[$this->file_type] ?? 'gray';
    }

    public function isViewableInBrowser(): bool
    {
        $viewable = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp4'];
        return in_array($this->file_type, $viewable);
    }

    public function canBeDownloaded(): bool
    {
        return !empty($this->file_path) && file_exists(storage_path('app/' . $this->file_path));
    }

    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'badge-secondary',
            self::STATUS_SUBMITTED => 'badge-primary',
            self::STATUS_REVIEWED => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    public function submit(): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    // Media Library
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('report_file')
            ->singleFile();

        $this->addMediaCollection('pdf_preview')
            ->singleFile();
    }
}
