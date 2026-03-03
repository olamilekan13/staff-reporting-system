<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Models\Video;
use App\Services\VideoCategoryService;
use App\Services\VideoService;
use App\Services\WatchTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
    private const ALLOWED_HTML_TAGS = '<p><br><strong><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6><a><blockquote><pre><code>';

    public function __construct(
        protected VideoService $videoService,
        protected VideoCategoryService $categoryService,
        protected WatchTrackingService $trackingService
    ) {}

    public function index(Request $request)
    {
        $videos = $this->videoService->getAllVideos([
            'status' => $request->query('status'),
            'category_id' => $request->query('category_id'),
            'search' => $request->query('search'),
        ]);

        $categories = $this->categoryService->getActiveCategories();

        return view('admin.videos.index', compact('videos', 'categories'));
    }

    public function create()
    {
        Gate::authorize('create', Video::class);

        $categories = $this->categoryService->getActiveCategories();
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);
        $users = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.videos.create', compact('categories', 'departments', 'users'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Video::class);

        $validator = Validator::make($request->all(), [
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'video_category_id' => 'nullable|exists:video_categories,id',
            'source_type'       => 'required|in:upload,youtube,vimeo,m3u8,embed',
            'video_file'        => 'required_if:source_type,upload|nullable|file|mimes:mp4,webm,mov|max:512000',
            'source_url'        => 'required_if:source_type,youtube,vimeo,m3u8|nullable|url:http,https|max:2000',
            'embed_code'        => 'required_if:source_type,embed|nullable|string|max:5000',
            'thumbnail'         => 'nullable|image|mimes:jpeg,png,webp|max:5120',
            'duration_seconds'  => 'nullable|integer|min:0',
            'status'            => 'required|in:draft,published,archived',
            'target_type'       => 'required|in:all,departments,users',
            'department_ids'    => 'required_if:target_type,departments|array',
            'department_ids.*'  => 'exists:departments,id',
            'user_ids'          => 'required_if:target_type,users|array',
            'user_ids.*'        => 'exists:users,id',
            'publish_at'        => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        if (!empty($data['description'])) {
            $data['description'] = strip_tags($data['description'], self::ALLOWED_HTML_TAGS);
        }

        // Handle embed code: store in source_url, sanitize
        if ($data['source_type'] === 'embed' && !empty($data['embed_code'])) {
            $data['source_url'] = strip_tags($data['embed_code'], '<iframe>');
        }

        $video = $this->videoService->createVideo(Auth::user(), $data);

        // Handle file uploads via Spatie MediaLibrary
        if ($request->hasFile('video_file') && $data['source_type'] === 'upload') {
            $video->addMediaFromRequest('video_file')
                ->toMediaCollection('video_file');
        }

        if ($request->hasFile('thumbnail')) {
            $video->addMediaFromRequest('thumbnail')
                ->toMediaCollection('video_thumbnail');
        }

        return redirect()->route('admin.videos.index')
            ->with('success', 'Video created successfully.');
    }

    public function show(Video $video)
    {
        $video->load(['category', 'creator', 'departments', 'users']);
        $stats = $this->trackingService->getVideoStats($video);

        return view('admin.videos.show', compact('video', 'stats'));
    }

    public function edit(Video $video)
    {
        Gate::authorize('update', $video);

        $video->load(['departments', 'users']);
        $categories = $this->categoryService->getActiveCategories();
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);
        $users = User::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.videos.edit', compact('video', 'categories', 'departments', 'users'));
    }

    public function update(Request $request, Video $video)
    {
        Gate::authorize('update', $video);

        $validator = Validator::make($request->all(), [
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'video_category_id' => 'nullable|exists:video_categories,id',
            'source_type'       => 'required|in:upload,youtube,vimeo,m3u8,embed',
            'video_file'        => 'nullable|file|mimes:mp4,webm,mov|max:512000',
            'source_url'        => 'required_if:source_type,youtube,vimeo,m3u8|nullable|url:http,https|max:2000',
            'embed_code'        => 'required_if:source_type,embed|nullable|string|max:5000',
            'thumbnail'         => 'nullable|image|mimes:jpeg,png,webp|max:5120',
            'duration_seconds'  => 'nullable|integer|min:0',
            'status'            => 'required|in:draft,published,archived',
            'target_type'       => 'required|in:all,departments,users',
            'department_ids'    => 'required_if:target_type,departments|array',
            'department_ids.*'  => 'exists:departments,id',
            'user_ids'          => 'required_if:target_type,users|array',
            'user_ids.*'        => 'exists:users,id',
            'publish_at'        => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        if (!empty($data['description'])) {
            $data['description'] = strip_tags($data['description'], self::ALLOWED_HTML_TAGS);
        }

        if ($data['source_type'] === 'embed' && !empty($data['embed_code'])) {
            $data['source_url'] = strip_tags($data['embed_code'], '<iframe>');
        }

        $this->videoService->updateVideo($video, $data);

        if ($request->hasFile('video_file') && $data['source_type'] === 'upload') {
            $video->clearMediaCollection('video_file');
            $video->addMediaFromRequest('video_file')
                ->toMediaCollection('video_file');
        }

        if ($request->hasFile('thumbnail')) {
            $video->clearMediaCollection('video_thumbnail');
            $video->addMediaFromRequest('thumbnail')
                ->toMediaCollection('video_thumbnail');
        }

        return redirect()->route('admin.videos.index')
            ->with('success', 'Video updated successfully.');
    }

    public function destroy(Video $video)
    {
        Gate::authorize('delete', $video);

        $this->videoService->deleteVideo($video);

        return redirect()->route('admin.videos.index')
            ->with('success', 'Video deleted successfully.');
    }
}
