<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CommentResource;
use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Proposal;
use App\Models\Report;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class CommentController extends ApiController
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}
    #[OA\Get(
        path: '/api/v1/reports/{report}/comments',
        summary: 'List report comments',
        description: 'Get threaded comments for a report with nested replies',
        operationId: 'listReportComments',
        tags: ['Comments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'report', in: 'path', required: true, description: 'Report ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comments retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Comments retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Comment')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Report not found'),
        ]
    )]
    public function indexForReport(Report $report): JsonResponse
    {
        $this->authorizeViewComments($report);

        $comments = $this->getThreadedComments($report);

        return $this->successResponse(
            CommentResource::collection($comments),
            'Comments retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/reports/{report}/comments',
        summary: 'Create report comment',
        description: 'Add a comment to a report',
        operationId: 'createReportComment',
        tags: ['Comments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'report', in: 'path', required: true, description: 'Report ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 5000, example: 'Great report!'),
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, description: 'Parent comment ID for replies'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Comment created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Comment created successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Comment'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Report not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storeForReport(Request $request, Report $report): JsonResponse
    {
        return $this->storeComment($request, $report);
    }

    #[OA\Get(
        path: '/api/v1/proposals/{proposal}/comments',
        summary: 'List proposal comments',
        description: 'Get threaded comments for a proposal with nested replies',
        operationId: 'listProposalComments',
        tags: ['Comments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'proposal', in: 'path', required: true, description: 'Proposal ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comments retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Comments retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Comment')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Proposal not found'),
        ]
    )]
    public function indexForProposal(Proposal $proposal): JsonResponse
    {
        $this->authorizeViewComments($proposal);

        $comments = $this->getThreadedComments($proposal);

        return $this->successResponse(
            CommentResource::collection($comments),
            'Comments retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/proposals/{proposal}/comments',
        summary: 'Create proposal comment',
        description: 'Add a comment to a proposal',
        operationId: 'createProposalComment',
        tags: ['Comments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'proposal', in: 'path', required: true, description: 'Proposal ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 5000, example: 'Great proposal!'),
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, description: 'Parent comment ID for replies'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Comment created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Comment created successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Comment'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Proposal not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storeForProposal(Request $request, Proposal $proposal): JsonResponse
    {
        return $this->storeComment($request, $proposal);
    }

    #[OA\Put(
        path: '/api/v1/comments/{comment}',
        summary: 'Update comment',
        description: 'Update a comment (owner only)',
        operationId: 'updateComment',
        tags: ['Comments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'comment', in: 'path', required: true, description: 'Comment ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 5000, example: 'Updated comment content'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comment updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Comment updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Comment'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - not owner'),
            new OA\Response(response: 404, description: 'Comment not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Comment $comment): JsonResponse
    {
        Gate::authorize('update', $comment);

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $oldContent = $comment->content;

        $comment->update([
            'content' => $request->content,
        ]);

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $comment,
            ['content' => $oldContent],
            ['content' => $request->content]
        );

        $comment->load(['user', 'allReplies']);

        return $this->successResponse(
            new CommentResource($comment),
            'Comment updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/v1/comments/{comment}',
        summary: 'Delete comment',
        description: 'Soft delete a comment (owner or admin)',
        operationId: 'deleteComment',
        tags: ['Comments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'comment', in: 'path', required: true, description: 'Comment ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comment deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Comment deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Comment not found'),
        ]
    )]
    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        ActivityLog::log(ActivityLog::ACTION_DELETE, $comment);

        $comment->delete();

        return $this->successResponse(null, 'Comment deleted successfully');
    }

    /**
     * Get threaded comments (parent comments with nested replies).
     * Uses allReplies for unlimited nesting depth.
     */
    private function getThreadedComments(Report|Proposal $commentable)
    {
        return $commentable->comments()
            ->parentOnly()
            ->with(['user', 'allReplies'])
            ->latest()
            ->get();
    }

    /**
     * Store a comment for a commentable resource.
     */
    private function storeComment(Request $request, Report|Proposal $commentable): JsonResponse
    {
        $this->authorizeCreateComment($commentable);

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:5000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // Validate parent comment belongs to same commentable
        if ($request->parent_id) {
            $parentComment = Comment::find($request->parent_id);
            if (
                $parentComment->commentable_type !== get_class($commentable) ||
                $parentComment->commentable_id !== $commentable->id
            ) {
                return $this->validationErrorResponse([
                    'parent_id' => ['Parent comment does not belong to this resource.']
                ]);
            }
        }

        $user = $request->user();

        $comment = Comment::create([
            'commentable_type' => get_class($commentable),
            'commentable_id' => $commentable->id,
            'user_id' => $user->id,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        ActivityLog::log(ActivityLog::ACTION_CREATE, $comment);

        // Load relationships needed for notification
        $comment->load(['user', 'commentable', 'parent.user']);

        // Notify via NotificationService (handles owner, parent comment author, email, KingsChat)
        $this->notificationService->notifyComment($comment);

        $comment->load(['allReplies']);

        return $this->createdResponse(
            new CommentResource($comment),
            'Comment created successfully'
        );
    }

    /**
     * Authorize viewing comments on a resource.
     */
    private function authorizeViewComments(Report|Proposal $commentable): void
    {
        Gate::authorize('viewAny', [Comment::class, $commentable]);
    }

    /**
     * Authorize creating a comment on a resource.
     */
    private function authorizeCreateComment(Report|Proposal $commentable): void
    {
        Gate::authorize('create', [Comment::class, $commentable]);
    }
}
