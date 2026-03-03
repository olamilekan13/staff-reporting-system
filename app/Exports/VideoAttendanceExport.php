<?php

namespace App\Exports;

use App\Models\Video;
use App\Models\VideoWatchLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VideoAttendanceExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        protected Video $video,
        protected array $filters = []
    ) {}

    public function query()
    {
        $query = VideoWatchLog::query()
            ->with(['user', 'user.department'])
            ->where('watchable_type', Video::class)
            ->where('watchable_id', $this->video->id);

        if (!empty($this->filters['department_id'])) {
            $query->whereHas('user', fn($q) => $q->where('department_id', $this->filters['department_id']));
        }

        return $query->orderBy('started_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'User',
            'Department',
            'Started At',
            'Ended At',
            'Duration (minutes)',
            'Completed',
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
            $log->completed ? 'Yes' : 'No',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
