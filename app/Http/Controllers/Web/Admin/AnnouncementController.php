<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
    private const ALLOWED_HTML_TAGS = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6><a><blockquote><pre><code><table><thead><tbody><tr><th><td>';

    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    public function index(Request $request)
    {
        $announcements = $this->announcementService->getAllAnnouncements([
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ]);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        Gate::authorize('create', Announcement::class);

        $departments = Department::active()->orderBy('name')->get(['id', 'name']);
        $users = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.announcements.create', compact('departments', 'users'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Announcement::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'target_type' => 'required|in:all,departments,users',
            'department_ids' => 'required_if:target_type,departments|array',
            'department_ids.*' => 'exists:departments,id',
            'user_ids' => 'required_if:target_type,users|array',
            'user_ids.*' => 'exists:users,id',
            'is_pinned' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['content'] = strip_tags($data['content'], self::ALLOWED_HTML_TAGS);
        $data['is_pinned'] = $request->boolean('is_pinned');

        $this->announcementService->createAnnouncement(Auth::user(), $data);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    public function show(Announcement $announcement)
    {
        return redirect()->route('announcements.show', $announcement);
    }

    public function edit(Announcement $announcement)
    {
        Gate::authorize('update', $announcement);

        $announcement->load(['departments', 'users']);
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);
        $users = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.announcements.edit', compact('announcement', 'departments', 'users'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        Gate::authorize('update', $announcement);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'target_type' => 'required|in:all,departments,users',
            'department_ids' => 'required_if:target_type,departments|array',
            'department_ids.*' => 'exists:departments,id',
            'user_ids' => 'required_if:target_type,users|array',
            'user_ids.*' => 'exists:users,id',
            'is_pinned' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['content'] = strip_tags($data['content'], self::ALLOWED_HTML_TAGS);
        $data['is_pinned'] = $request->boolean('is_pinned');

        $this->announcementService->updateAnnouncement($announcement, $data);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        Gate::authorize('delete', $announcement);

        $this->announcementService->deleteAnnouncement($announcement);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }
}
