<?php

namespace App\Exports;

use App\Models\VideoWatchLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LivestreamAttendanceExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function query()
    {
        $query = VideoWatchLog::query()
            ->with(['user', 'user.department'])
            ->where('source', VideoWatchLog::SOURCE_LIVESTREAM);

        if (!empty($this->filters['department_id'])) {
            $query->whereHas('user', fn($q) => $q->where('department_id', $this->filters['department_id']));
        }

        if (!empty($this->filters['date_from'])) {
            $query->where('started_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('started_at', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('started_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'User',
            'Department',
            'Joined At',
            'Left At',
            'Duration (minutes)',
        ];
    }

    public function map($log): array
    {
        return [
            $log->user?->full_name ?? 'Unknown',
            $log->user?->department?->name ?? '',
            $log->started_at?->format('Y-m-d H:i:s'),
            $log->ended_at?->format('Y-m-d H:i:s') ?? 'Still watching',
            round($log->duration_seconds / 60, 1),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
