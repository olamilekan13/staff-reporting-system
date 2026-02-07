<?php

namespace App\Http\Controllers\Web\Staff;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $reports = $this->reportService->getReportsForUser($user, [
            'category' => $request->query('category'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
        ]);

        return view('staff.reports.index', compact('reports'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Report::class);

        $category = $request->query('category');

        return view('staff.reports.create', compact('category'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Report::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'report_category' => 'required|in:daily,weekly,monthly,quarterly,annual',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,mp4,mov,avi',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        $data = array_merge($request->only(['title', 'description', 'report_category']), [
            'report_type' => Report::TYPE_PERSONAL,
        ]);

        if ($request->filled('description')) {
            $data['description'] = clean($request->input('description'));
        }

        $report = $this->reportService->createReport(
            $user,
            $data,
            $request->file('file')
        );

        if ($request->input('action') === 'submit') {
            $this->reportService->submitReport($report);
            return redirect()->route('staff.reports.show', $report)
                ->with('success', 'Report submitted successfully.');
        }

        return redirect()->route('staff.reports.show', $report)
            ->with('success', 'Report saved as draft.');
    }

    public function show(Report $report)
    {
        Gate::authorize('view', $report);

        $report->load(['user', 'department', 'reviewer', 'comments' => function ($q) {
            $q->parentOnly()->with(['user', 'allReplies'])->latest();
        }]);

        return view('staff.reports.show', compact('report'));
    }

    public function edit(Report $report)
    {
        Gate::authorize('update', $report);

        return view('staff.reports.edit', compact('report'));
    }

    public function update(Request $request, Report $report)
    {
        Gate::authorize('update', $report);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'report_category' => 'required|in:daily,weekly,monthly,quarterly,annual',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,mp4,mov,avi',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->only(['title', 'description', 'report_category']);

        if ($request->filled('description')) {
            $data['description'] = clean($request->input('description'));
        }

        $this->reportService->updateReport(
            $report,
            $data,
            $request->file('file')
        );

        if ($request->input('action') === 'submit') {
            $this->reportService->submitReport($report->fresh());
            return redirect()->route('staff.reports.show', $report)
                ->with('success', 'Report submitted successfully.');
        }

        return redirect()->route('staff.reports.show', $report)
            ->with('success', 'Report updated successfully.');
    }

    public function destroy(Report $report)
    {
        Gate::authorize('delete', $report);

        $this->reportService->deleteReport($report);

        return redirect()->route('staff.reports.index')
            ->with('success', 'Report deleted successfully.');
    }
}
