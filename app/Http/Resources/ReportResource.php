<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'report_type' => $this->report_type,
            'report_category' => $this->report_category,
            'status' => $this->status,
            'status_badge_class' => $this->getStatusBadgeClass(),

            // File info
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'formatted_file_size' => $this->when($this->file_size, fn () => $this->getFormattedFileSize()),
            'file_icon' => $this->when($this->file_type, fn () => $this->getFileIcon()),
            'file_color' => $this->when($this->file_type, fn () => $this->getFileColor()),
            'file_url' => $this->getFileUrl(),
            'preview_url' => $this->getPreviewUrl(),
            'office_online_preview_url' => $this->getOfficeOnlinePreviewUrl(),
            'is_viewable_in_browser' => $this->isViewableInBrowser(),
            'can_be_downloaded' => $this->when($this->file_path, fn () => $this->hasMedia('report_file')),

            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->when(
                $this->relationLoaded('comments'),
                fn () => $this->comments->count()
            ),

            // Review info
            'review_notes' => $this->review_notes,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),

            // Timestamps
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    protected function getFileUrl(): ?string
    {
        $media = $this->getFirstMedia('report_file');

        return $media?->getUrl();
    }

    protected function getPreviewUrl(): ?string
    {
        $pdfPreview = $this->getFirstMedia('pdf_preview');

        return $pdfPreview?->getUrl();
    }

    protected function getOfficeOnlinePreviewUrl(): ?string
    {
        $officeExtensions = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

        if (!$this->file_type || !in_array(strtolower($this->file_type), $officeExtensions)) {
            return null;
        }

        if ($this->getFirstMedia('pdf_preview')) {
            return null;
        }

        $media = $this->getFirstMedia('report_file');

        if (!$media) {
            return null;
        }

        $signedUrl = URL::temporarySignedRoute(
            'media.serve',
            now()->addMinutes(30),
            ['media' => $media->id]
        );

        return 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($signedUrl);
    }
}
