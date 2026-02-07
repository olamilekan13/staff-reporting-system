<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProposalService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}
    /**
     * Get proposals filtered by user's role and access level.
     */
    public function getProposalsForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Proposal::query()->with(['user', 'reviewer']);

        // Apply role-based visibility
        if ($user->isAdmin() || $user->hasRole('head_of_operations')) {
            // Admin and Head of Operations see all proposals
        } else {
            // Regular staff sees only their own proposals
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
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
     * Create a new proposal with optional file upload.
     */
    public function createProposal(User $user, array $data, ?UploadedFile $file = null): Proposal
    {
        $proposal = Proposal::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => Proposal::STATUS_PENDING,
        ]);

        if ($file) {
            $this->attachFile($proposal, $file);
        }

        ActivityLog::log(ActivityLog::ACTION_CREATE, $proposal);

        return $proposal->fresh(['user']);
    }

    /**
     * Update an existing proposal.
     */
    public function updateProposal(Proposal $proposal, array $data, ?UploadedFile $file = null): Proposal
    {
        $oldValues = $proposal->only(['title', 'description']);

        $proposal->update([
            'title' => $data['title'] ?? $proposal->title,
            'description' => $data['description'] ?? $proposal->description,
        ]);

        if ($file) {
            $this->replaceFile($proposal, $file);
        }

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $proposal,
            $oldValues,
            $proposal->only(['title', 'description'])
        );

        return $proposal->fresh(['user']);
    }

    /**
     * Soft delete a proposal and its file.
     */
    public function deleteProposal(Proposal $proposal): bool
    {
        // Clear media collection (MediaLibrary handles file deletion)
        $proposal->clearMediaCollection('proposal_attachment');

        ActivityLog::log(ActivityLog::ACTION_DELETE, $proposal);

        return $proposal->delete();
    }

    /**
     * Review a proposal (set status to under_review, approved, or rejected).
     */
    public function reviewProposal(Proposal $proposal, User $reviewer, string $status, ?string $notes = null): Proposal
    {
        $oldStatus = $proposal->status;

        $proposal->update([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        // Notify proposal owner via NotificationService (handles in-app, email, KingsChat)
        $this->notificationService->notifyProposalStatus($proposal);

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $proposal,
            ['status' => $oldStatus],
            ['status' => $status, 'reviewed_by' => $reviewer->id]
        );

        return $proposal->fresh(['user', 'reviewer']);
    }

    /**
     * Get file download stream.
     */
    public function getFileStream(Proposal $proposal): ?StreamedResponse
    {
        $media = $proposal->getFirstMedia('proposal_attachment');

        if (!$media) {
            return null;
        }

        ActivityLog::log(ActivityLog::ACTION_DOWNLOAD, $proposal);

        return Storage::disk($media->disk)->download(
            $media->getPathRelativeToRoot(),
            $media->file_name
        );
    }

    /**
     * Attach a file to a proposal using MediaLibrary.
     */
    private function attachFile(Proposal $proposal, UploadedFile $file): void
    {
        // Capture metadata before addMedia() moves the temp file
        $metadata = [
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'file_path' => 'media',
        ];

        $proposal->addMedia($file)
            ->toMediaCollection('proposal_attachment');

        $proposal->update($metadata);
    }

    /**
     * Replace existing file with a new one.
     */
    private function replaceFile(Proposal $proposal, UploadedFile $file): void
    {
        // Clear existing media
        $proposal->clearMediaCollection('proposal_attachment');

        // Add new file
        $this->attachFile($proposal, $file);
    }
}
