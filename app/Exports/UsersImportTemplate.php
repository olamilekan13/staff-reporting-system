<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersImportTemplate implements FromArray, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'kingschat_id',
            'title',
            'first_name',
            'last_name',
            'email',
            'phone',
            'department_name',
            'role',
        ];
    }

    public function array(): array
    {
        return [
            [
                'john.doe',
                'Brother',
                'John',
                'Doe',
                'john@example.com',
                '+1234567890',
                'Engineering',
                'staff',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
