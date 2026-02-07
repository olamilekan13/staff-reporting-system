<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Proposal;
use App\Models\Report;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function storeForReport(Request $request, Report $report): JsonResponse
    {
        Gate::authorize('view', $report);

        return $this->storeComment($request, $report);
    }

    public function storeForProposal(Request $request, Proposal $proposal): JsonResponse
    {
        Gate::authorize('view', $proposal);

        return $this->storeComment($request, $proposal);
    }

    private function storeComment(Request $request, Report|Proposal $commentable): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if ($request->parent_id) {
            $parent = Comment::find($request->parent_id);
            if (
                $parent->commentable_type !== get_class($commentable) ||
                $parent->commentable_id !== $commentable->id
            ) {
                return response()->json([
                    'message' => 'Parent comment does not belong to this resource.',
                ], 422);
            }
        }

        $user = Auth::user();

        $comment = Comment::create([
            'commentable_type' => get_class($commentable),
            'commentable_id' => $commentable->id,
            'user_id' => $user->id,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        ActivityLog::log(ActivityLog::ACTION_CREATE, $comment);

        $comment->load(['user', 'commentable', 'parent.user']);
        $this->notificationService->notifyComment($comment);

        return response()->json([
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'user_name' => $comment->user->full_name,
                'created_at_human' => $comment->created_at->diffForHumans(),
                'replies' => [],
            ],
        ]);
    }
}
