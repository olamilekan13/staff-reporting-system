<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\Proposal;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $reportCounts = Report::where('user_id', $user->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $proposalCounts = Proposal::where('user_id', $user->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentReports = Report::where('user_id', $user->id)
            ->with('department')
            ->latest()
            ->take(10)
            ->get();

        $unreadNotificationCount = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

        $latestNotifications = Notification::where('user_id', $user->id)
            ->unread()
            ->latestFirst()
            ->take(3)
            ->get();

        $announcements = Announcement::active()
            ->forUser($user)
            ->orderByDesc('is_pinned')
            ->latest()
            ->take(5)
            ->get();

        $reportLinks = $user->reportLinks()->latest()->get();

        return view('staff.dashboard', compact(
            'reportCounts',
            'proposalCounts',
            'recentReports',
            'unreadNotificationCount',
            'latestNotifications',
            'announcements',
            'reportLinks',
        ));
    }
}
