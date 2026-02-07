@extends('layouts.app')

@section('title', $proposal->title)
@section('page-title', 'View Proposal')

@section('content')
    {{-- Back link --}}
    <a href="{{ route('admin.proposals.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to Proposals
    </a>

    {{-- Proposal header --}}
    <x-card>
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $proposal->title }}</h2>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    @php
                        $statusBadge = match($proposal->status) {
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'under_review' => 'primary',
                            'pending' => 'warning',
                            default => 'info',
                        };
                    @endphp
                    <x-badge :type="$statusBadge">{{ ucfirst(str_replace('_', ' ', $proposal->status)) }}</x-badge>
                </div>
                <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-500">
                    <span>By {{ $proposal->user->full_name }}</span>
                    <span>&middot; Created {{ $proposal->created_at->format('M d, Y \a\t g:i A') }}</span>
                </div>
            </div>
        </div>
    </x-card>

    {{-- Description --}}
    @if($proposal->description)
        <x-card title="Description" class="mt-6">
            <div class="text-sm text-gray-700">{!! $proposal->description !!}</div>
        </x-card>
    @endif

    {{-- File viewer --}}
    @if($proposal->file_name)
        @php
            $media = $proposal->getFirstMedia('proposal_attachment');
            $pdfPreview = $proposal->getFirstMedia('pdf_preview');

            $downloadUrl = $media
                ? \Illuminate\Support\Facades\URL::temporarySignedRoute('media.serve', now()->addMinutes(30), ['media' => $media->id])
                : null;
            $previewUrl = $pdfPreview
                ? \Illuminate\Support\Facades\URL::temporarySignedRoute('media.serve', now()->addMinutes(30), ['media' => $pdfPreview->id])
                : null;
        @endphp
        <x-card title="Attachment" class="mt-6">
            <x-file-viewer
                :file-name="$proposal->file_name"
                :file-type="$proposal->file_type"
                :file-size="$proposal->getFormattedFileSize()"
                :download-url="$downloadUrl"
                :preview-url="$previewUrl"
                :mime-type="$proposal->mime_type ?? null"
            />
        </x-card>
    @endif

    {{-- Review section --}}
    @can('review', $proposal)
        @if(in_array($proposal->status, ['pending', 'under_review']))
            <x-card title="Review Proposal" class="mt-6">
                <form method="POST" action="{{ route('admin.proposals.review', $proposal) }}">
                    @csrf

                    <div class="mb-4">
                        <label for="admin_notes" class="label">Review Notes</label>
                        <textarea name="admin_notes" id="admin_notes" rows="3" placeholder="Add notes about your review decision..."
                            class="input @error('admin_notes') border-red-300 @enderror">{{ old('admin_notes') }}</textarea>
                        @error('admin_notes')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        @if($proposal->status === 'pending')
                            <x-button type="submit" name="status" value="under_review" variant="primary">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Mark Under Review
                            </x-button>
                        @endif
                        <x-button type="submit" name="status" value="approved" variant="success">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Approve
                        </x-button>
                        <x-button type="submit" name="status" value="rejected" variant="danger">
                            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Reject
                        </x-button>
                    </div>
                </form>
            </x-card>
        @endif
    @endcan

    {{-- Review result --}}
    @if($proposal->reviewed_at)
        <x-card title="Review" class="mt-6">
            <div class="space-y-3">
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Reviewed by:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $proposal->reviewer?->full_name ?? 'Unknown' }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Reviewed on:</span>
                    <span class="text-sm text-gray-900">{{ $proposal->reviewed_at->format('M d, Y \a\t g:i A') }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500">Decision:</span>
                    @php
                        $reviewBadge = match($proposal->status) {
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'under_review' => 'primary',
                            default => 'warning',
                        };
                    @endphp
                    <x-badge :type="$reviewBadge">{{ ucfirst(str_replace('_', ' ', $proposal->status)) }}</x-badge>
                </div>
                @if($proposal->admin_notes)
                    <div>
                        <span class="text-sm text-gray-500">Notes:</span>
                        <p class="text-sm text-gray-700 mt-1 whitespace-pre-wrap">{{ $proposal->admin_notes }}</p>
                    </div>
                @endif
            </div>
        </x-card>
    @endif

    {{-- Comments section --}}
    <div class="mt-6" x-data="commentsSection({{ $proposal->id }})">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Comments (<span x-text="comments.length">{{ $proposal->comments->count() }}</span>)</h3>
            </div>

            {{-- Add comment form --}}
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
                                <button @click="startReply(comment.id)" class="text-xs text-primary-600 hover:text-primary-700 mt-1">Reply</button>

                                <template x-if="replyingTo === comment.id">
                                    <form @submit.prevent="addReply(comment.id)" class="mt-3">
                                        <textarea x-model="replyContent" rows="2" placeholder="Write a reply..." class="input w-full text-sm" required></textarea>
                                        <div class="flex items-center gap-2 mt-2">
                                            <x-button type="submit" variant="primary" size="sm" x-bind:disabled="submitting">Reply</x-button>
                                            <button type="button" @click="replyingTo = null" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                                        </div>
                                    </form>
                                </template>

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
        $commentsData = $proposal->comments->map(function ($comment) {
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
        function commentsSection(resourceId) {
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
                        const response = await fetch(`/proposals/${resourceId}/comments`, {
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
                        const response = await fetch(`/proposals/${resourceId}/comments`, {
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
