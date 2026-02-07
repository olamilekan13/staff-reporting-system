<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = User::query()->with(['department', 'roles']);

        if (!empty($this->filters['department_id'])) {
            $query->byDepartment($this->filters['department_id']);
        }

        if (!empty($this->filters['role'])) {
            $query->role($this->filters['role']);
        }

        if (isset($this->filters['is_active'])) {
            $query->where('is_active', filter_var($this->filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('kingschat_id', 'like', "%{$search}%");
            });
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'KingsChat ID',
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Department',
            'Role',
            'Status',
            'Last Login',
            'Created At',
        ];
    }

    public function map($user): array
    {
        return [
            $user->kingschat_id,
            $user->first_name,
            $user->last_name,
            $user->email ?? '',
            $user->phone ?? '',
            $user->department?->name ?? '',
            $user->roles->first()?->name ?? '',
            $user->is_active ? 'Active' : 'Inactive',
            $user->last_login_at?->format('Y-m-d H:i:s') ?? 'Never',
            $user->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
