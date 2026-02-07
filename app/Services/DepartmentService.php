<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService
{
    /**
     * Get all departments with head and staff count.
     */
    public function getDepartments(): Collection
    {
        return Department::with(['head', 'parent', 'children'])
            ->withCount(['users as staff_count' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new department.
     */
    public function createDepartment(array $data): Department
    {
        $department = Department::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'head_id' => $data['head_id'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'is_active' => true,
        ]);

        ActivityLog::log(ActivityLog::ACTION_CREATE, $department);

        return $department->load(['head', 'parent']);
    }

    /**
     * Get department with staff and relationships loaded.
     */
    public function getDepartmentWithStaff(Department $department): Department
    {
        return $department->load(['head', 'parent', 'children', 'users.roles']);
    }

    /**
     * Update department fields.
     */
    public function updateDepartment(Department $department, array $data): Department
    {
        $oldValues = $department->only(['name', 'description', 'head_id', 'parent_id']);

        $department->update([
            'name' => $data['name'] ?? $department->name,
            'description' => $data['description'] ?? $department->description,
            'head_id' => array_key_exists('head_id', $data) ? $data['head_id'] : $department->head_id,
            'parent_id' => array_key_exists('parent_id', $data) ? $data['parent_id'] : $department->parent_id,
        ]);

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $department,
            $oldValues,
            $department->only(['name', 'description', 'head_id', 'parent_id'])
        );

        return $department->load(['head', 'parent']);
    }

    /**
     * Delete department only if no users are assigned.
     *
     * @return bool|string True on success, error message string on failure.
     */
    public function deleteDepartment(Department $department): bool|string
    {
        if ($department->users()->exists()) {
            return 'Cannot delete department with assigned users. Reassign users first.';
        }

        ActivityLog::log(ActivityLog::ACTION_DELETE, $department);

        $department->delete();

        return true;
    }

    /**
     * Get paginated staff list for a department.
     */
    public function getDepartmentStaff(Department $department, array $filters = []): LengthAwarePaginator
    {
        $query = $department->users()->with(['roles']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Assign a user to a department.
     */
    public function assignUserToDepartment(Department $department, User $user): User
    {
        $oldDepartmentId = $user->department_id;

        $user->update(['department_id' => $department->id]);

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $user,
            ['department_id' => $oldDepartmentId],
            ['department_id' => $department->id]
        );

        return $user->load(['department', 'roles']);
    }
}
