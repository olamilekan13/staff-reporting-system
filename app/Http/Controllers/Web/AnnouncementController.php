<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $announcements = $this->announcementService->getAnnouncementsForUser($user, [
            'priority' => $request->query('priority'),
        ]);

        return view('announcements.index', compact('announcements', 'user'));
    }

    public function show(Announcement $announcement)
    {
        Gate::authorize('view', $announcement);

        $user = Auth::user();
        $announcement = $this->announcementService->getAnnouncementForUser($announcement, $user);

        return view('announcements.show', compact('announcement'));
    }
}
