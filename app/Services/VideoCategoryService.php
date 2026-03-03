<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\VideoCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VideoCategoryService
{
    public function getAllCategories(array $filters = []): LengthAwarePaginator
    {
        $query = VideoCategory::query()->withCount('videos');

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $query->ordered();

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getActiveCategories(): Collection
    {
        return VideoCategory::active()->ordered()->get();
    }

    public function createCategory(array $data): VideoCategory
    {
        $data['slug'] = Str::slug($data['name']);

        // Ensure unique slug
        $originalSlug = $data['slug'];
        $counter = 1;
        while (VideoCategory::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $counter++;
        }

        $category = VideoCategory::create($data);

        ActivityLog::log(ActivityLog::ACTION_CREATE, $category);

        return $category;
    }

    public function updateCategory(VideoCategory $category, array $data): VideoCategory
    {
        $oldValues = $category->only(['name', 'description', 'sort_order', 'is_active']);

        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = Str::slug($data['name']);
            $originalSlug = $data['slug'];
            $counter = 1;
            while (VideoCategory::where('slug', $data['slug'])->where('id', '!=', $category->id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter++;
            }
        }

        $category->update($data);

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $category,
            $oldValues,
            $category->only(['name', 'description', 'sort_order', 'is_active'])
        );

        return $category;
    }

    public function deleteCategory(VideoCategory $category): bool
    {
        ActivityLog::log(ActivityLog::ACTION_DELETE, $category);

        return $category->delete();
    }
}
