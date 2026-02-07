<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Proposal;
use App\Models\Report;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_reports' => Report::count(),
            'pending_reports' => Report::byStatus(Report::STATUS_SUBMITTED)->count(),
            'pending_proposals' => Proposal::pending()->count(),
            'active_announcements' => Announcement::active()->count(),
        ];

        $recentReports = Report::with(['user', 'department'])
            ->latest()
            ->take(10)
            ->get();

        $recentActivity = ActivityLog::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentReports', 'recentActivity'));
    }
}
