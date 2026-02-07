<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'head_id',
        'parent_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function announcements()
    {
        return $this->belongsToMany(Announcement::class, 'announcement_department')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // Helper Methods
    public function getStaffCount(): int
    {
        return $this->users()->active()->count();
    }

    public function getAllStaffIds(): array
    {
        $staffIds = $this->users()->pluck('id')->toArray();

        // Include staff from child departments
        foreach ($this->children as $child) {
            $staffIds = array_merge($staffIds, $child->getAllStaffIds());
        }

        return array_unique($staffIds);
    }

    public function getFullHierarchy(): array
    {
        $hierarchy = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($hierarchy, $parent->name);
            $parent = $parent->parent;
        }

        return $hierarchy;
    }
}
