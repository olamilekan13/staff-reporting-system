<?php

namespace App\Http\Controllers\Web\Hod;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $department = $user->department;

        if (!$department) {
            return view('hod.dashboard', [
                'department' => null,
                'stats' => [],
                'reportsToReview' => collect(),
                'recentDepartmentReports' => collect(),
            ]);
        }

        $staffCount = $department->getStaffCount();

        $stats = [
            'total_department_reports' => Report::byDepartment($department->id)->count(),
            'reports_to_review' => Report::byDepartment($department->id)
                ->byStatus(Report::STATUS_SUBMITTED)
                ->where('user_id', '!=', $user->id)
                ->count(),
            'my_reports' => Report::where('user_id', $user->id)->count(),
            'staff_count' => $staffCount,
        ];

        $reportsToReview = Report::byDepartment($department->id)
            ->byStatus(Report::STATUS_SUBMITTED)
            ->where('user_id', '!=', $user->id)
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        $recentDepartmentReports = Report::byDepartment($department->id)
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        $recentVideos = Announcement::whereIn('announcement_type', [
            'video_upload', 'audio_upload', 'youtube', 'vimeo', 'livestream',
        ])
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->limit(5)
            ->get();

        return view('hod.dashboard', compact(
            'department',
            'stats',
            'reportsToReview',
            'recentDepartmentReports',
            'recentVideos',
        ));
    }
}
