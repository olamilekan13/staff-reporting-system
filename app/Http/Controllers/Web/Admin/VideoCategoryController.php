<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoCategory;
use App\Services\VideoCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class VideoCategoryController extends Controller
{
    public function __construct(
        protected VideoCategoryService $categoryService
    ) {}

    public function index(Request $request)
    {
        $categories = $this->categoryService->getAllCategories([
            'search' => $request->query('search'),
        ]);

        return view('admin.video-categories.index', compact('categories'));
    }

    public function create()
    {
        Gate::authorize('create', VideoCategory::class);

        return view('admin.video-categories.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', VideoCategory::class);

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->categoryService->createCategory($data);

        return redirect()->route('admin.video-categories.index')
            ->with('success', 'Video category created successfully.');
    }

    public function edit(VideoCategory $videoCategory)
    {
        Gate::authorize('update', $videoCategory);

        return view('admin.video-categories.edit', ['category' => $videoCategory]);
    }

    public function update(Request $request, VideoCategory $videoCategory)
    {
        Gate::authorize('update', $videoCategory);

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->categoryService->updateCategory($videoCategory, $data);

        return redirect()->route('admin.video-categories.index')
            ->with('success', 'Video category updated successfully.');
    }

    public function destroy(VideoCategory $videoCategory)
    {
        Gate::authorize('delete', $videoCategory);

        if ($videoCategory->videos()->exists()) {
            return back()->with('error', 'Cannot delete category with existing videos. Please reassign or delete the videos first.');
        }

        $this->categoryService->deleteCategory($videoCategory);

        return redirect()->route('admin.video-categories.index')
            ->with('success', 'Video category deleted successfully.');
    }
}
