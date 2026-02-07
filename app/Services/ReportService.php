<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}
    /**
     * Get reports filtered by user's role and access level.
     */
    public function getReportsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Report::query()->with(['user', 'department', 'reviewer']);

        // Apply role-based visibility
        if ($user->isAdmin() || $user->hasRole('head_of_operations')) {
            // Admin and Head of Operations see all reports
        } elseif ($user->isHOD()) {
            // HOD sees own reports + department reports
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('department_id', $user->department_id);
            });
        } else {
            // Regular staff sees only their own reports
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['department_id'])) {
            $query->byDepartment($filters['department_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new report with optional file upload.
     */
    public function createReport(User $user, array $data, ?UploadedFile $file = null): Report
    {
        $report = Report::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'report_type' => $data['report_type'] ?? Report::TYPE_PERSONAL,
            'report_category' => $data['report_category'] ?? Report::CATEGORY_WEEKLY,
            'status' => Report::STATUS_DRAFT,
        ]);

        if ($file) {
            $this->attachFile($report, $file);
        }

        ActivityLog::log(ActivityLog::ACTION_CREATE, $report);

        return $report->fresh(['user', 'department']);
    }

    /**
     * Update an existing report.
     */
    public function updateReport(Report $report, array $data, ?UploadedFile $file = null): Report
    {
        $oldValues = $report->only(['title', 'description', 'report_type', 'report_category']);

        $report->update([
            'title' => $data['title'] ?? $report->title,
            'description' => $data['description'] ?? $report->description,
            'report_type' => $data['report_type'] ?? $report->report_type,
            'report_category' => $data['report_category'] ?? $report->report_category,
        ]);

        if ($file) {
            $this->replaceFile($report, $file);
        }

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $report,
            $oldValues,
            $report->only(['title', 'description', 'report_type', 'report_category'])
        );

        return $report->fresh(['user', 'department']);
    }

    /**
     * Soft delete a report and its file.
     */
    public function deleteReport(Report $report): bool
    {
        // Clear media collection (MediaLibrary handles file deletion)
        $report->clearMediaCollection('report_file');

        ActivityLog::log(ActivityLog::ACTION_DELETE, $report);

        return $report->delete();
    }

    /**
     * Submit a report for review.
     */
    public function submitReport(Report $report): Report
    {
        $report->update([
            'status' => Report::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        // Notify relevant HOD or admin
        $reviewers = $this->getReviewers($report);
        $this->notificationService->notifyReportSubmission($report, $reviewers);

        ActivityLog::log(ActivityLog::ACTION_UPDATE, $report, ['status' => 'draft'], ['status' => 'submitted']);

        return $report->fresh(['user', 'department']);
    }

    /**
     * Review a report (approve/reject).
     */
    public function reviewReport(Report $report, User $reviewer, string $status, ?string $notes = null): Report
    {
        $oldStatus = $report->status;

        $report->update([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        // Notify report owner via NotificationService (handles in-app, email, KingsChat)
        $this->notificationService->notifyReportStatus($report);

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $report,
            ['status' => $oldStatus],
            ['status' => $status, 'reviewed_by' => $reviewer->id]
        );

        return $report->fresh(['user', 'department', 'reviewer']);
    }

    /**
     * Get file download stream.
     */
    public function getFileStream(Report $report): ?StreamedResponse
    {
        $media = $report->getFirstMedia('report_file');

        if (!$media) {
            return null;
        }

        ActivityLog::log(ActivityLog::ACTION_DOWNLOAD, $report);

        return Storage::disk($media->disk)->download(
            $media->getPathRelativeToRoot(),
            $media->file_name
        );
    }

    /**
     * Attach a file to a report using MediaLibrary.
     */
    private function attachFile(Report $report, UploadedFile $file): void
    {
        // Capture metadata before addMedia() moves the temp file
        $metadata = [
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_path' => 'media',
        ];

        $report->addMedia($file)
            ->toMediaCollection('report_file');

        $report->update($metadata);
    }

    /**
     * Replace existing file with a new one.
     */
    private function replaceFile(Report $report, UploadedFile $file): void
    {
        // Clear existing media
        $report->clearMediaCollection('report_file');

        // Add new file
        $this->attachFile($report, $file);
    }

    /**
     * Get relevant reviewers for a submitted report.
     */
    private function getReviewers(Report $report): \Illuminate\Support\Collection
    {
        $reviewers = collect();

        // Get department HOD
        if ($report->department && $report->department->head) {
            $reviewers->push($report->department->head);
        }

        // Get admins
        $admins = User::role(['admin', 'head_of_operations'])->active()->get();
        $reviewers = $reviewers->merge($admins)->unique('id');

        return $reviewers;
    }
}
