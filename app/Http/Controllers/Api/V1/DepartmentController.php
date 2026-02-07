<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\UserResource;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class DepartmentController extends ApiController
{
    public function __construct(
        protected DepartmentService $departmentService
    ) {}

    #[OA\Get(
        path: '/api/v1/departments',
        summary: 'List departments',
        description: 'Get all departments with head and staff count. Available to all authenticated users.',
        operationId: 'listDepartments',
        tags: ['Departments'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Departments retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Departments retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Department')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Department::class);

        $departments = $this->departmentService->getDepartments();

        return $this->successResponse(
            DepartmentResource::collection($departments),
            'Departments retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/departments',
        summary: 'Create a department',
        description: 'Create a new department. Admin only.',
        operationId: 'createDepartment',
        tags: ['Departments'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Engineering'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 1000, example: 'Software development team'),
                    new OA\Property(property: 'head_id', type: 'integer', description: 'User ID of department head', example: 1),
                    new OA\Property(property: 'parent_id', type: 'integer', description: 'Parent department ID', example: null),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Department created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Department created successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Department'),
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
        Gate::authorize('create', Department::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string|max:1000',
            'head_id' => 'nullable|integer|exists:users,id',
            'parent_id' => 'nullable|integer|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $department = $this->departmentService->createDepartment($validator->validated());

        return $this->createdResponse(
            new DepartmentResource($department),
            'Department created successfully'
        );
    }

    #[OA\Get(
        path: '/api/v1/departments/{department}',
        summary: 'Get a department',
        description: 'Get department details with staff list. Available to all authenticated users.',
        operationId: 'getDepartment',
        tags: ['Departments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'department', in: 'path', required: true, description: 'Department ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Department retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Department retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Department'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Department not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Department $department): JsonResponse
    {
        Gate::authorize('view', $department);

        $department = $this->departmentService->getDepartmentWithStaff($department);

        return $this->successResponse(
            new DepartmentResource($department),
            'Department retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/api/v1/departments/{department}',
        summary: 'Update a department',
        description: 'Update department fields. Can reassign head. Admin only.',
        operationId: 'updateDepartment',
        tags: ['Departments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'department', in: 'path', required: true, description: 'Department ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string', maxLength: 1000),
                    new OA\Property(property: 'head_id', type: 'integer', nullable: true, description: 'User ID of department head. Set to null to remove head.'),
                    new OA\Property(property: 'parent_id', type: 'integer', nullable: true, description: 'Parent department ID. Set to null to make root department.'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Department updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Department updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Department'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Department not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(Request $request, Department $department): JsonResponse
    {
        Gate::authorize('update', $department);

        $validator = Validator::make($request->all(), [
            'name' => "sometimes|required|string|max:255|unique:departments,name,{$department->id}",
            'description' => 'nullable|string|max:1000',
            'head_id' => 'nullable|integer|exists:users,id',
            'parent_id' => "nullable|integer|exists:departments,id|not_in:{$department->id}",
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $department = $this->departmentService->updateDepartment($department, $validator->validated());

        return $this->successResponse(
            new DepartmentResource($department),
            'Department updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/v1/departments/{department}',
        summary: 'Delete a department',
        description: 'Delete a department. Only allowed if no users are assigned to it. Admin only.',
        operationId: 'deleteDepartment',
        tags: ['Departments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'department', in: 'path', required: true, description: 'Department ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Department deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Department deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Department not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Department has assigned users', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Department $department): JsonResponse
    {
        Gate::authorize('delete', $department);

        $result = $this->departmentService->deleteDepartment($department);

        if ($result !== true) {
            return $this->errorResponse($result, 422);
        }

        return $this->successResponse(null, 'Department deleted successfully');
    }

    #[OA\Get(
        path: '/api/v1/departments/{department}/staff',
        summary: 'List department staff',
        description: 'Get paginated list of users in a department. Available to all authenticated users.',
        operationId: 'listDepartmentStaff',
        tags: ['Departments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'department', in: 'path', required: true, description: 'Department ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search by name or email', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page (max 100)', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Department staff retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Department staff retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Department not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function staff(Request $request, Department $department): JsonResponse
    {
        Gate::authorize('view', $department);

        $filters = $request->only(['search', 'per_page']);
        $staff = $this->departmentService->getDepartmentStaff($department, $filters);

        return $this->paginatedResponse(
            UserResource::collection($staff),
            'Department staff retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/departments/{department}/staff',
        summary: 'Assign user to department',
        description: 'Assign a user to this department. Admin only.',
        operationId: 'assignStaffToDepartment',
        tags: ['Departments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'department', in: 'path', required: true, description: 'Department ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User assigned to department successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'User assigned to department successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Department or user not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function assignStaff(Request $request, Department $department): JsonResponse
    {
        Gate::authorize('manageStaff', $department);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = User::findOrFail($request->user_id);
        $user = $this->departmentService->assignUserToDepartment($department, $user);

        return $this->successResponse(
            new UserResource($user),
            'User assigned to department successfully'
        );
    }
}
