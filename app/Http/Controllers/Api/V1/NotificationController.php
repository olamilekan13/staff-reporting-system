<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class NotificationController extends ApiController
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    #[OA\Get(
        path: '/api/v1/notifications',
        summary: 'List user notifications',
        description: 'Get paginated notifications for the authenticated user with optional filters',
        operationId: 'listNotifications',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'type',
                in: 'query',
                description: 'Filter by notification type',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['comment', 'announcement', 'report_status', 'proposal_status', 'system']
                )
            ),
            new OA\Parameter(
                name: 'unread',
                in: 'query',
                description: 'Filter to show only unread notifications',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Number of items per page (max 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notifications retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Notifications retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Notification')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string|in:comment,announcement,report_status,proposal_status,system',
            'unread' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $filters = $request->only(['type', 'unread', 'per_page']);

        if (isset($filters['unread'])) {
            $filters['unread'] = filter_var($filters['unread'], FILTER_VALIDATE_BOOLEAN);
        }

        $notifications = $this->notificationService->getNotificationsForUser(
            $request->user(),
            $filters
        );

        return $this->paginatedResponse(
            NotificationResource::collection($notifications),
            'Notifications retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/v1/notifications/unread-count',
        summary: 'Get unread notification count',
        description: 'Returns the number of unread notifications for the authenticated user',
        operationId: 'getUnreadNotificationCount',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Unread count retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Unread count retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'count', type: 'integer', example: 5),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());

        return $this->successResponse(
            ['count' => $count],
            'Unread count retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/notifications/{notification}/read',
        summary: 'Mark notification as read',
        description: 'Mark a single notification as read',
        operationId: 'markNotificationAsRead',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'notification',
                in: 'path',
                required: true,
                description: 'Notification UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification marked as read',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Notification marked as read'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Notification'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - not owner'),
            new OA\Response(response: 404, description: 'Notification not found'),
        ]
    )]
    public function markAsRead(Notification $notification): JsonResponse
    {
        Gate::authorize('update', $notification);

        $notification->markAsRead();

        return $this->successResponse(
            new NotificationResource($notification),
            'Notification marked as read'
        );
    }

    #[OA\Post(
        path: '/api/v1/notifications/read-all',
        summary: 'Mark all notifications as read',
        description: 'Mark all unread notifications as read for the authenticated user',
        operationId: 'markAllNotificationsAsRead',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All notifications marked as read',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'All notifications marked as read'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'updated_count', type: 'integer', example: 10),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return $this->successResponse(
            ['updated_count' => $count],
            'All notifications marked as read'
        );
    }

    #[OA\Delete(
        path: '/api/v1/notifications/{notification}',
        summary: 'Delete a notification',
        description: 'Delete a notification (owner only)',
        operationId: 'deleteNotification',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'notification',
                in: 'path',
                required: true,
                description: 'Notification UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Notification deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - not owner'),
            new OA\Response(response: 404, description: 'Notification not found'),
        ]
    )]
    public function destroy(Notification $notification): JsonResponse
    {
        Gate::authorize('delete', $notification);

        $this->notificationService->deleteNotification($notification);

        return $this->successResponse(null, 'Notification deleted successfully');
    }
}
