<?php

namespace App\Services;

use App\Exports\UsersExport;
use App\Exports\UsersImportTemplate;
use App\Imports\UsersImport;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserService
{
    /**
     * Get paginated users with filters.
     */
    public function getUsers(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()->with(['department', 'roles']);

        if (!empty($filters['department_id'])) {
            $query->byDepartment($filters['department_id']);
        }

        if (!empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('kingschat_id', 'like', "%{$search}%");
            });
        }

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new user and assign role.
     */
    public function createUser(array $data): User
    {
        $user = User::create([
            'kingschat_id' => $data['kingschat_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'is_active' => true,
        ]);

        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        ActivityLog::log(ActivityLog::ACTION_CREATE, $user);

        return $user->load(['department', 'roles']);
    }

    /**
     * Get user with activity summary.
     */
    public function getUserWithActivitySummary(User $user): User
    {
        $user->load(['department', 'roles']);

        $user->setAttribute('activity_summary', [
            'total_reports' => $user->reports()->count(),
            'submitted_reports' => $user->reports()->where('status', 'submitted')->count(),
            'approved_reports' => $user->reports()->where('status', 'approved')->count(),
            'total_proposals' => $user->proposals()->count(),
            'total_comments' => $user->comments()->count(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ]);

        return $user;
    }

    /**
     * Update user fields (not kingschat_id). Can change role and department.
     */
    public function updateUser(User $user, array $data): User
    {
        $oldValues = $user->only(['first_name', 'last_name', 'email', 'phone', 'department_id']);

        $user->update([
            'first_name' => $data['first_name'] ?? $user->first_name,
            'last_name' => $data['last_name'] ?? $user->last_name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'department_id' => $data['department_id'] ?? $user->department_id,
        ]);

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $user,
            $oldValues,
            $user->only(['first_name', 'last_name', 'email', 'phone', 'department_id'])
        );

        return $user->load(['department', 'roles']);
    }

    /**
     * Soft deactivate user and revoke all tokens.
     */
    public function deactivateUser(User $user): User
    {
        $user->update(['is_active' => false]);
        $user->tokens()->delete();

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $user,
            ['is_active' => true],
            ['is_active' => false]
        );

        return $user;
    }

    /**
     * Reactivate a deactivated user.
     */
    public function activateUser(User $user): User
    {
        $user->update(['is_active' => true]);

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $user,
            ['is_active' => false],
            ['is_active' => true]
        );

        return $user;
    }

    /**
     * Import users from Excel file.
     *
     * @return array{success_count: int, failures: array}
     */
    public function importUsers(UploadedFile $file): array
    {
        $import = new UsersImport();
        Excel::import($import, $file);

        return [
            'success_count' => $import->getSuccessCount(),
            'failures' => $import->getFormattedFailures(),
        ];
    }

    /**
     * Download Excel import template.
     */
    public function getImportTemplate(): BinaryFileResponse
    {
        return Excel::download(new UsersImportTemplate(), 'users_import_template.xlsx');
    }

    /**
     * Export users to Excel based on filters.
     */
    public function exportUsers(array $filters = []): BinaryFileResponse
    {
        return Excel::download(new UsersExport($filters), 'users_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    /**
     * Preview import data without saving to database.
     *
     * @return array{preview: array, errors: array}
     */
    public function previewImport(UploadedFile $file): array
    {
        $data = Excel::toCollection(new HeadingRowImport(), $file)->first();

        $preview = [];
        $errors = [];

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // +2 because: 1 for header, 1 for 0-based index

            $preview[] = [
                'row' => $rowNumber,
                'kingschat_id' => $row['kingschat_id'] ?? '',
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'email' => $row['email'] ?? '',
                'phone' => $row['phone'] ?? '',
                'department_name' => $row['department_name'] ?? '',
                'role' => $row['role'] ?? 'staff',
            ];

            // Basic validation for preview
            $rowErrors = [];

            if (empty($row['kingschat_id'])) {
                $rowErrors[] = 'KingsChat ID is required';
            } elseif (User::where('kingschat_id', $row['kingschat_id'])->exists()) {
                $rowErrors[] = 'KingsChat ID already exists';
            }

            if (empty($row['first_name'])) {
                $rowErrors[] = 'First name is required';
            }

            if (empty($row['last_name'])) {
                $rowErrors[] = 'Last name is required';
            }

            if (!empty($row['email']) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Invalid email format';
            }

            if (!empty($row['role']) && !in_array($row['role'], ['admin', 'head_of_operations', 'hod', 'staff'])) {
                $rowErrors[] = 'Invalid role';
            }

            if (!empty($rowErrors)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $rowErrors,
                ];
            }
        }

        return [
            'preview' => $preview,
            'errors' => $errors,
        ];
    }
}
