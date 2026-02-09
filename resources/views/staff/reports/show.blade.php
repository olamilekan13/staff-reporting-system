@extends('layouts.app')

@section('title', $report->title)
@section('page-title', 'View Report')

@section('content')
    {{-- Back link --}}
    <a href="{{ route('staff.reports.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to Reports
    </a>

    {{-- Report header --}}
    <x-card>
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $report->title }}</h2>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    @php
                        $statusBadge = match($report->status) {
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'submitted', 'reviewed' => 'warning',
                            default => 'info',
                        };
                    @endphp
                    <x-badge :type="$statusBadge">{{ ucfirst($report->status) }}</x-badge>
                    <x-badge type="info">{{ ucfirst($report->report_category) }}</x-badge>
                    <x-badge type="primary">{{ ucfirst($report->report_type) }}</x-badge>
                </div>
                <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-500">
                    @if($report->submitted_at)
                        <span>Submitted {{ $report->submitted_at->format('M d, Y \a\t g:i A') }}</span>
                    @else
                        <span>Created {{ $report->created_at->format('M d, Y \a\t g:i A') }}</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @can('update', $report)
                    <x-button variant="secondary" size="sm" :href="route('staff.reports.edit', $report)">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                        </svg>
                        Edit
                    </x-button>
                @endcan

                {{-- Share to KingsChat Button --}}
                <x-share-kingschat-button
                    :title="'Report: ' . $report->title"
                    :url="route('staff.reports.show', $report)"
                    type="report"
                    variant="secondary"
                />

                @can('delete', $report)
                    <form method="POST" action="{{ route('staff.reports.destroy', $report) }}" onsubmit="return confirm('Are you sure you want to delete this report?')">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger" size="sm">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            Delete
                        </x-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-card>

    {{-- Description --}}
    @if($report->description)
        <x-card title="Description" class="mt-6">
            <div class="text-sm text-gray-700 prose prose-sm max-w-none">{!! $report->description !!}</div>
        </x-card>
    @endif

    {{-- File viewer --}}
    @if($report->file_name)
        @php
            $media = $report->getFirstMedia('report_file');
            $pdfPreview = $report->getFirstMedia('pdf_preview');

            $downloadUrl = $media
                ? \Illuminate\Support\Facades\URL::temporarySignedRoute('media.serve', now()->addMinutes(30), ['media' => $media->id])
                : null;
            $previewUrl = $pdfPreview
                ? \Illuminate\Support\Facades\URL::temporarySignedRoute('media.serve', now()->addMinutes(30), ['media' => $pdfPreview->id])
                : null;
        @endphp
        <x-card title="Attachment" class="mt-6">
            <x-file-viewer
                :file-name="$report->file_name"
                :file-type="$report->file_type"
                :file-size="$report->getFormattedFileSize()"
                :download-url="$downloadUrl"
                :preview-url="$previewUrl"
                :mime-type="$report->mime_type ?? null"
            />
        </x-card>
    @endif

    {{-- Review info (read-only for staff) --}}
    @if($report->reviewed_at)
        <x-card title="Review" class="mt-6">
            <div class="space-y-3">
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Reviewed by:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $report->reviewer?->full_name ?? 'Unknown' }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Reviewed on:</span>
                    <span class="text-sm text-gray-900">{{ $report->reviewed_at->format('M d, Y \a\t g:i A') }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Decision:</span>
                    @php
                        $reviewBadge = $report->status === 'approved' ? 'success' : 'danger';
                    @endphp
                    <x-badge :type="$reviewBadge">{{ ucfirst($report->status) }}</x-badge>
                </div>
                @if($report->review_notes)
                    <div>
                        <span class="text-sm text-gray-500">Notes:</span>
                        <p class="text-sm text-gray-700 mt-1 whitespace-pre-wrap">{{ $report->review_notes }}</p>
                    </div>
                @endif
            </div>
        </x-card>
    @endif

    {{-- Comments section --}}
    <div class="mt-6" x-data="commentsSection({{ $report->id }}, 'report')">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Comments (<span x-text="comments.length">{{ $report->comments->count() }}</span>)</h3>
            </div>

            {{-- Add comment form - Staff cannot create comments --}}
            @can('create', [App\Models\Comment::class, $report])
                <form @submit.prevent="addComment" class="mb-6">
                    <textarea x-model="newComment" rows="3" placeholder="Write a comment..."
                        class="input w-full" required></textarea>
                    <div class="flex justify-end mt-2">
                        <x-button type="submit" variant="primary" size="sm" x-bind:disabled="submitting">
                            <span x-show="!submitting">Post Comment</span>
                            <span x-show="submitting">Posting...</span>
                        </x-button>
                    </div>
                </form>
            @else
                <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <p class="text-sm text-gray-600">
                        <svg class="w-5 h-5 inline-block mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        Only administrators and department heads can add comments.
                    </p>
                </div>
            @endcan

            {{-- Comments list --}}
            <div class="space-y-4">
                <template x-if="comments.length === 0">
                    <p class="text-sm text-gray-500 text-center py-4">No comments yet. Be the first to comment.</p>
                </template>

                <template x-for="comment in comments" :key="comment.id">
                    <div class="border-b border-gray-100 pb-4 last:border-0">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold shrink-0" x-text="comment.user_name.charAt(0).toUpperCase()"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900" x-text="comment.user_name"></span>
                                    <span class="text-xs text-gray-400" x-text="comment.created_at_human"></span>
                                </div>
                                <p class="text-sm text-gray-700 mt-1 whitespace-pre-wrap" x-text="comment.content"></p>
                                <div class="flex items-center gap-2 mt-1">
                                    <button @click="startReply(comment.id)" class="text-xs text-primary-600 hover:text-primary-700">Reply</button>
                                    <button
                                        type="button"
                                        x-data="shareToKingsChat"
                                        @click="share(`Comment by ${comment.user_name}`, '{{ url()->current() }}#comment-' + comment.id, 'comment')"
                                        class="text-xs text-gray-400 hover:text-primary-600 flex items-center gap-1"
                                        title="Share comment"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                        </svg>
                                        Share
                                    </button>
                                </div>

                                {{-- Reply form --}}
                                <template x-if="replyingTo === comment.id">
                                    <form @submit.prevent="addReply(comment.id)" class="mt-3">
                                        <textarea x-model="replyContent" rows="2" placeholder="Write a reply..." class="input w-full text-sm" required></textarea>
                                        <div class="flex items-center gap-2 mt-2">
                                            <x-button type="submit" variant="primary" size="sm" x-bind:disabled="submitting">Reply</x-button>
                                            <button type="button" @click="replyingTo = null" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                                        </div>
                                    </form>
                                </template>

                                {{-- Replies --}}
                                <template x-if="comment.replies && comment.replies.length > 0">
                                    <div class="mt-3 ml-4 space-y-3 border-l-2 border-gray-100 pl-4">
                                        <template x-for="reply in comment.replies" :key="reply.id">
                                            <div class="flex items-start gap-3">
                                                <div class="w-6 h-6 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-xs font-bold shrink-0" x-text="reply.user_name.charAt(0).toUpperCase()"></div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-medium text-gray-900" x-text="reply.user_name"></span>
                                                        <span class="text-xs text-gray-400" x-text="reply.created_at_human"></span>
                                                    </div>
                                                    <p class="text-sm text-gray-700 mt-0.5 whitespace-pre-wrap" x-text="reply.content"></p>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </x-card>
    </div>

    @php
        $commentsData = $report->comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'user_name' => $comment->user->full_name,
                'created_at_human' => $comment->created_at->diffForHumans(),
                'replies' => $comment->allReplies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'content' => $reply->content,
                        'user_name' => $reply->user->full_name,
                        'created_at_human' => $reply->created_at->diffForHumans(),
                    ];
                }),
            ];
        });
    @endphp

    @push('scripts')
    <script>
        function commentsSection(resourceId, resourceType) {
            return {
                comments: @json($commentsData),
                newComment: '',
                replyContent: '',
                replyingTo: null,
                submitting: false,

                startReply(commentId) {
                    this.replyingTo = this.replyingTo === commentId ? null : commentId;
                    this.replyContent = '';
                },

                async addComment() {
                    if (!this.newComment.trim() || this.submitting) return;
                    this.submitting = true;

                    try {
                        const response = await fetch(`/reports/${resourceId}/comments`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ content: this.newComment }),
                        });

                        const data = await response.json();

                        if (response.ok) {
                            this.comments.unshift(data.comment);
                            this.newComment = '';
                        } else {
                            this.$dispatch('toast', { type: 'error', title: data.message || 'Failed to post comment.' });
                        }
                    } catch (e) {
                        this.$dispatch('toast', { type: 'error', title: 'An error occurred.' });
                    } finally {
                        this.submitting = false;
                    }
                },

                async addReply(parentId) {
                    if (!this.replyContent.trim() || this.submitting) return;
                    this.submitting = true;

                    try {
                        const response = await fetch(`/reports/${resourceId}/comments`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ content: this.replyContent, parent_id: parentId }),
                        });

                        const data = await response.json();

                        if (response.ok) {
                            const comment = this.comments.find(c => c.id === parentId);
                            if (comment) {
                                if (!comment.replies) comment.replies = [];
                                comment.replies.push(data.comment);
                            }
                            this.replyContent = '';
                            this.replyingTo = null;
                        } else {
                            this.$dispatch('toast', { type: 'error', title: data.message || 'Failed to post reply.' });
                        }
                    } catch (e) {
                        this.$dispatch('toast', { type: 'error', title: 'An error occurred.' });
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
    @endpush
@endsection
