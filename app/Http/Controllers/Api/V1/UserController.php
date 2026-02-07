<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends ApiController
{
    public function __construct(
        protected UserService $userService
    ) {}

    #[OA\Get(
        path: '/api/v1/users',
        summary: 'List users',
        description: 'Get paginated list of users with optional filters. Admin only.',
        operationId: 'listUsers',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'department_id', in: 'query', description: 'Filter by department', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'role', in: 'query', description: 'Filter by role', schema: new OA\Schema(type: 'string', enum: ['admin', 'head_of_operations', 'hod', 'staff'])),
            new OA\Parameter(name: 'is_active', in: 'query', description: 'Filter by active status', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search by name, email, or kingschat_id', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (max 100)', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Users retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Users retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->only(['department_id', 'role', 'is_active', 'search', 'per_page']);

        $users = $this->userService->getUsers($filters);

        return $this->paginatedResponse(
            UserResource::collection($users),
            'Users retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/users',
        summary: 'Create a user',
        description: 'Create a new user and assign a role. Admin only.',
        operationId: 'createUser',
        tags: ['Users'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kingschat_id', 'first_name', 'last_name'],
                properties: [
                    new OA\Property(property: 'kingschat_id', type: 'string', maxLength: 255, example: 'john.doe'),
                    new OA\Property(property: 'first_name', type: 'string', maxLength: 255, example: 'John'),
                    new OA\Property(property: 'last_name', type: 'string', maxLength: 255, example: 'Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'john@example.com'),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20, example: '+1234567890'),
                    new OA\Property(property: 'department_id', type: 'integer', example: 1),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'head_of_operations', 'hod', 'staff'], example: 'staff'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'User created successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $validator = Validator::make($request->all(), [
            'kingschat_id' => 'required|string|max:255|unique:users,kingschat_id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'department_id' => 'nullable|integer|exists:departments,id',
            'role' => 'nullable|string|in:admin,head_of_operations,hod,staff',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = $this->userService->createUser($validator->validated());

        return $this->createdResponse(
            new UserResource($user),
            'User created successfully'
        );
    }

    #[OA\Get(
        path: '/api/v1/users/{user}',
        summary: 'Get a user',
        description: 'Get user details with activity summary. Admin only.',
        operationId: 'getUser',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'User ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'User retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            allOf: [
                                new OA\Schema(ref: '#/components/schemas/User'),
                                new OA\Schema(
                                    properties: [
                                        new OA\Property(
                                            property: 'activity_summary',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: 'total_reports', type: 'integer', example: 10),
                                                new OA\Property(property: 'submitted_reports', type: 'integer', example: 5),
                                                new OA\Property(property: 'approved_reports', type: 'integer', example: 3),
                                                new OA\Property(property: 'total_proposals', type: 'integer', example: 2),
                                                new OA\Property(property: 'total_comments', type: 'integer', example: 15),
                                                new OA\Property(property: 'last_login_at', type: 'string', format: 'date-time', nullable: true),
                                            ]
                                        ),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        $user = $this->userService->getUserWithActivitySummary($user);

        $data = (new UserResource($user))->toArray(request());
        $data['activity_summary'] = $user->activity_summary;

        return $this->successResponse($data, 'User retrieved successfully');
    }

    #[OA\Put(
        path: '/api/v1/users/{user}',
        summary: 'Update a user',
        description: 'Update user fields (not kingschat_id). Can change role and department. Admin only.',
        operationId: 'updateUser',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'User ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'last_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20),
                    new OA\Property(property: 'department_id', type: 'integer'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'head_of_operations', 'hod', 'staff']),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'User updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(Request $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'department_id' => 'nullable|integer|exists:departments,id',
            'role' => 'nullable|string|in:admin,head_of_operations,hod,staff',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = $this->userService->updateUser($user, $validator->validated());

        return $this->successResponse(
            new UserResource($user),
            'User updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/v1/users/{user}',
        summary: 'Deactivate a user',
        description: 'Soft deactivate a user (set is_active = false). Does not delete the user. Admin only. Cannot deactivate yourself.',
        operationId: 'deactivateUser',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'User ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User deactivated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'User deactivated successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Cannot deactivate yourself', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Request $request, User $user): JsonResponse
    {
        Gate::authorize('delete', $user);

        if ($request->user()->id === $user->id) {
            return $this->errorResponse('You cannot deactivate your own account.', 422);
        }

        $this->userService->deactivateUser($user);

        return $this->successResponse(null, 'User deactivated successfully');
    }

    #[OA\Post(
        path: '/api/v1/users/{user}/activate',
        summary: 'Activate a user',
        description: 'Reactivate a deactivated user. Admin only.',
        operationId: 'activateUser',
        tags: ['Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'User ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User activated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'User activated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function activate(User $user): JsonResponse
    {
        Gate::authorize('activate', $user);

        $user = $this->userService->activateUser($user);

        return $this->successResponse(
            new UserResource($user->load(['department', 'roles'])),
            'User activated successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/users/import',
        summary: 'Import users from Excel',
        description: 'Upload an Excel file to bulk import users. Returns success count and failed rows with errors. Admin only.',
        operationId: 'importUsers',
        tags: ['Users'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Excel file (.xlsx, .xls, .csv). Max 10MB.'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Import completed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Import completed: 10 users imported successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'success_count', type: 'integer', example: 10),
                                new OA\Property(
                                    property: 'failures',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'row', type: 'integer', example: 3),
                                            new OA\Property(property: 'attribute', type: 'string', example: 'kingschat_id'),
                                            new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string')),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function import(Request $request): JsonResponse
    {
        Gate::authorize('import', User::class);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $result = $this->userService->importUsers($request->file('file'));

        $message = "Import completed: {$result['success_count']} users imported successfully";
        if (!empty($result['failures'])) {
            $failCount = count($result['failures']);
            $message .= ", {$failCount} rows failed";
        }

        return $this->successResponse($result, $message);
    }

    #[OA\Get(
        path: '/api/v1/users/import/template',
        summary: 'Download import template',
        description: 'Download an Excel template for user import. Admin only.',
        operationId: 'downloadUserImportTemplate',
        tags: ['Users'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Excel template file download',
                content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function importTemplate(Request $request): BinaryFileResponse
    {
        Gate::authorize('import', User::class);

        return $this->userService->getImportTemplate();
    }
}
