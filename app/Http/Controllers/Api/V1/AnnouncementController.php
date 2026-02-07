<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AnnouncementController extends ApiController
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    #[OA\Get(
        path: '/api/v1/announcements',
        summary: 'List announcements',
        description: 'Get paginated list of active announcements for the authenticated user',
        operationId: 'listAnnouncements',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcements retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Announcements retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Announcement')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Announcement::class);

        $filters = $request->only(['per_page']);

        $announcements = $this->announcementService->getAnnouncementsForUser(
            $request->user(),
            $filters
        );

        return $this->paginatedResponse(
            AnnouncementResource::collection($announcements),
            'Announcements retrieved successfully'
        );
    }

    #[OA\Get(
        path: '/api/v1/announcements/{announcement}',
        summary: 'Get announcement',
        description: 'Get a single announcement (automatically marks as read)',
        operationId: 'getAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'announcement', in: 'path', required: true, description: 'Announcement ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Announcement'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Announcement not found'),
        ]
    )]
    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        Gate::authorize('view', $announcement);

        $announcement = $this->announcementService->getAnnouncementForUser(
            $announcement,
            $request->user()
        );

        return $this->successResponse(
            new AnnouncementResource($announcement),
            'Announcement retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/announcements/{announcement}/read',
        summary: 'Mark announcement as read',
        description: 'Explicitly mark an announcement as read',
        operationId: 'markAnnouncementRead',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'announcement', in: 'path', required: true, description: 'Announcement ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement marked as read',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement marked as read'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Announcement not found'),
        ]
    )]
    public function markAsRead(Request $request, Announcement $announcement): JsonResponse
    {
        Gate::authorize('view', $announcement);

        $this->announcementService->markAsRead($announcement, $request->user());

        return $this->successResponse(null, 'Announcement marked as read');
    }

    #[OA\Post(
        path: '/api/v1/announcements',
        summary: 'Create announcement',
        description: 'Create a new announcement (Admin only)',
        operationId: 'createAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'content'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'System Maintenance'),
                    new OA\Property(property: 'content', type: 'string', example: 'The system will be down for maintenance...'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], example: 'high'),
                    new OA\Property(property: 'target_type', type: 'string', enum: ['all', 'departments', 'users'], example: 'all'),
                    new OA\Property(property: 'is_pinned', type: 'boolean', example: false),
                    new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'user_ids', type: 'array', items: new OA\Items(type: 'integer'), description: 'Required if target_type is users'),
                    new OA\Property(property: 'department_ids', type: 'array', items: new OA\Items(type: 'integer'), description: 'Required if target_type is departments'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Announcement created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement created successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Announcement'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Admin only'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Announcement::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'target_type' => 'nullable|in:all,departments,users',
            'is_pinned' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'user_ids' => 'required_if:target_type,users|array',
            'user_ids.*' => 'integer|exists:users,id',
            'department_ids' => 'required_if:target_type,departments|array',
            'department_ids.*' => 'integer|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $announcement = $this->announcementService->createAnnouncement(
            $request->user(),
            $validator->validated()
        );

        return $this->createdResponse(
            new AnnouncementResource($announcement),
            'Announcement created successfully'
        );
    }

    #[OA\Put(
        path: '/api/v1/announcements/{announcement}',
        summary: 'Update announcement',
        description: 'Update an announcement (Admin only)',
        operationId: 'updateAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'announcement', in: 'path', required: true, description: 'Announcement ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255),
                    new OA\Property(property: 'content', type: 'string'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent']),
                    new OA\Property(property: 'target_type', type: 'string', enum: ['all', 'departments', 'users']),
                    new OA\Property(property: 'is_pinned', type: 'boolean'),
                    new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'user_ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'department_ids', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Announcement'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Admin only'),
            new OA\Response(response: 404, description: 'Announcement not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        Gate::authorize('update', $announcement);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'target_type' => 'nullable|in:all,departments,users',
            'is_pinned' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'user_ids' => 'array',
            'user_ids.*' => 'integer|exists:users,id',
            'department_ids' => 'array',
            'department_ids.*' => 'integer|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $announcement = $this->announcementService->updateAnnouncement(
            $announcement,
            $validator->validated()
        );

        return $this->successResponse(
            new AnnouncementResource($announcement),
            'Announcement updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/v1/announcements/{announcement}',
        summary: 'Delete announcement',
        description: 'Soft delete an announcement (Admin only)',
        operationId: 'deleteAnnouncement',
        tags: ['Announcements'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'announcement', in: 'path', required: true, description: 'Announcement ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Announcement deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Announcement deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Admin only'),
            new OA\Response(response: 404, description: 'Announcement not found'),
        ]
    )]
    public function destroy(Announcement $announcement): JsonResponse
    {
        Gate::authorize('delete', $announcement);

        $this->announcementService->deleteAnnouncement($announcement);

        return $this->successResponse(null, 'Announcement deleted successfully');
    }
}
