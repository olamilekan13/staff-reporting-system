<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\VideoCategoryService;
use App\Services\VideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class VideoController extends Controller
{
    public function __construct(
        protected VideoService $videoService,
        protected VideoCategoryService $categoryService
    ) {}

    public function index(Request $request)
    {
        $videos = $this->videoService->getVideosForUser(Auth::user(), [
            'category_id' => $request->query('category_id'),
            'search' => $request->query('search'),
        ]);

        $categories = $this->categoryService->getActiveCategories();

        return view('videos.index', compact('videos', 'categories'));
    }

    public function show(Video $video)
    {
        Gate::authorize('view', $video);

        if (!$video->isPublished() && !Auth::user()->isAdmin()) {
            abort(404);
        }

        $video->load(['category', 'creator']);

        return view('videos.show', compact('video'));
    }
}
