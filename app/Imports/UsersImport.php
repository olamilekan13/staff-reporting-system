<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class UsersImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    private int $successCount = 0;

    private const ALLOWED_ROLES = ['admin', 'head_of_operations', 'hod', 'staff'];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $departmentId = $this->resolveDepartmentId($row['department_name'] ?? null);

            $user = User::create([
                'kingschat_id' => $row['kingschat_id'],
                'title' => $row['title'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'],
                'department_id' => $departmentId,
                'is_active' => true,
            ]);

            $role = $row['role'] ?? 'staff';
            if (in_array($role, self::ALLOWED_ROLES)) {
                $user->assignRole($role);
            } else {
                $user->assignRole('staff');
            }

            $this->successCount++;
        }
    }

    public function rules(): array
    {
        return [
            'kingschat_id' => 'required|string|unique:users,kingschat_id',
            'title' => 'required|string|in:Pastor,Deacon,Deaconess,Brother,Sister',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'department_name' => 'nullable|string|max:255',
            'role' => 'nullable|string|in:admin,head_of_operations,hod,staff',
        ];
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * Format failures for API response.
     *
     * @return array<int, array{row: int, errors: array<string>}>
     */
    public function getFormattedFailures(): array
    {
        return collect($this->failures())->map(function (Failure $failure) {
            return [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ];
        })->values()->toArray();
    }

    /**
     * Resolve department ID from name.
     */
    private function resolveDepartmentId(?string $departmentName): ?int
    {
        if (empty($departmentName)) {
            return null;
        }

        $department = Department::where('name', $departmentName)->first();

        return $department?->id;
    }
}
