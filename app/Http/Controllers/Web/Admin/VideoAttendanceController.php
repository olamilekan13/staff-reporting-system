<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exports\LivestreamAttendanceExport;
use App\Exports\VideoAttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Models\Video;
use App\Services\WatchTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class VideoAttendanceController extends Controller
{
    public function __construct(
        protected WatchTrackingService $trackingService
    ) {}

    public function videoAttendance(Video $video, Request $request)
    {
        Gate::authorize('viewAttendance', Video::class);

        $attendance = $this->trackingService->getVideoAttendance($video->id, [
            'department_id' => $request->query('department_id'),
            'search' => $request->query('search'),
        ]);

        $stats = $this->trackingService->getVideoStats($video);
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.videos.attendance', compact('video', 'attendance', 'stats', 'departments'));
    }

    public function userHistory(User $user, Request $request)
    {
        Gate::authorize('viewAttendance', Video::class);

        $watchHistory = $this->trackingService->getUserWatchHistory($user->id, [
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ]);

        return view('admin.videos.user-history', compact('user', 'watchHistory'));
    }

    public function livestreamAttendance(Request $request)
    {
        Gate::authorize('viewAttendance', Video::class);

        $attendance = $this->trackingService->getLivestreamAttendance([
            'department_id' => $request->query('department_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'search' => $request->query('search'),
        ]);

        $departments = Department::active()->orderBy('name')->get(['id', 'name']);

        return view('admin.videos.livestream-attendance', compact('attendance', 'departments'));
    }

    public function exportVideoAttendance(Video $video, Request $request)
    {
        Gate::authorize('viewAttendance', Video::class);

        $filters = ['department_id' => $request->query('department_id')];

        return Excel::download(
            new VideoAttendanceExport($video, $filters),
            'attendance-' . str_replace(' ', '-', strtolower($video->title)) . '-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportLivestreamAttendance(Request $request)
    {
        Gate::authorize('viewAttendance', Video::class);

        $filters = [
            'department_id' => $request->query('department_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        return Excel::download(
            new LivestreamAttendanceExport($filters),
            'livestream-attendance-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
