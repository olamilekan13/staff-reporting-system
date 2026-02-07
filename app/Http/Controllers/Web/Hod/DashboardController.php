<?php

namespace App\Http\Controllers\Web\Hod;

use App\Http\Controllers\Controller;
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

        return view('hod.dashboard', compact(
            'department',
            'stats',
            'reportsToReview',
            'recentDepartmentReports',
        ));
    }
}
