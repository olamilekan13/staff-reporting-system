<?php

namespace App\Http\Controllers\Web\Hod;

use App\Http\Controllers\Controller;
use App\Services\WatchTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoAttendanceController extends Controller
{
    public function __construct(
        protected WatchTrackingService $trackingService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $departmentId = $user->department_id;

        if (!$departmentId) {
            // Try to get department from headOfDepartment relationship
            $department = $user->headOfDepartment()->first();
            $departmentId = $department?->id;
        }

        if (!$departmentId) {
            abort(403, 'No department assigned.');
        }

        $attendance = $this->trackingService->getDepartmentAttendance($departmentId, [
            'source' => $request->query('source'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ]);

        return view('hod.videos.attendance', compact('attendance'));
    }
}
