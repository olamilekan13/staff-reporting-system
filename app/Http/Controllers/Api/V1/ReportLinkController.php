<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ReportLinkResource;
use App\Models\ReportLink;
use App\Models\User;
use App\Services\ReportLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ReportLinkController extends ApiController
{
    public function __construct(
        protected ReportLinkService $reportLinkService
    ) {}

    #[OA\Get(
        path: '/api/v1/users/{user}/report-links',
        summary: 'List user report links',
        description: 'Get all report links for a specific user. Super admin only.',
        operationId: 'listUserReportLinks',
        tags: ['Report Links'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'User ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Report links retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Report links retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ReportLink')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Super admin only'),
        ]
    )]
    public function index(User $user): JsonResponse
    {
        Gate::authorize('viewAny', ReportLink::class);

        $links = $this->reportLinkService->getLinksForUser($user);

        return $this->successResponse(
            ReportLinkResource::collection($links),
            'Report links retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/users/{user}/report-links',
        summary: 'Create report link',
        description: 'Create a new report link for a user. Super admin only.',
        operationId: 'createReportLink',
        tags: ['Report Links'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'User ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['url'],
                properties: [
                    new OA\Property(property: 'url', type: 'string', format: 'url', maxLength: 500, example: 'https://docs.google.com/document/d/xxxxx'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Report link created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Report link created successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ReportLink'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request, User $user): JsonResponse
    {
        Gate::authorize('create', ReportLink::class);

        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $link = $this->reportLinkService->createLink($user, $validator->validated());

        return $this->createdResponse(
            new ReportLinkResource($link),
            'Report link created successfully'
        );
    }

    #[OA\Get(
        path: '/api/v1/report-links/{reportLink}',
        summary: 'Get report link',
        description: 'Get a specific report link. Super admin only.',
        operationId: 'getReportLink',
        tags: ['Report Links'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'reportLink', in: 'path', required: true, description: 'Report Link ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Report link retrieved successfully'),
            new OA\Response(response: 404, description: 'Report link not found'),
        ]
    )]
    public function show(ReportLink $reportLink): JsonResponse
    {
        Gate::authorize('view', $reportLink);

        return $this->successResponse(
            new ReportLinkResource($reportLink->load('user')),
            'Report link retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/api/v1/report-links/{reportLink}',
        summary: 'Update report link',
        description: 'Update a report link URL. Super admin only.',
        operationId: 'updateReportLink',
        tags: ['Report Links'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'reportLink', in: 'path', required: true, description: 'Report Link ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['url'],
                properties: [
                    new OA\Property(property: 'url', type: 'string', format: 'url', maxLength: 500),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Report link updated successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, ReportLink $reportLink): JsonResponse
    {
        Gate::authorize('update', $reportLink);

        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $link = $this->reportLinkService->updateLink($reportLink, $validator->validated());

        return $this->successResponse(
            new ReportLinkResource($link),
            'Report link updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/v1/report-links/{reportLink}',
        summary: 'Delete report link',
        description: 'Delete a report link. Super admin only.',
        operationId: 'deleteReportLink',
        tags: ['Report Links'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'reportLink', in: 'path', required: true, description: 'Report Link ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Report link deleted successfully'),
        ]
    )]
    public function destroy(ReportLink $reportLink): JsonResponse
    {
        Gate::authorize('delete', $reportLink);

        $this->reportLinkService->deleteLink($reportLink);

        return $this->successResponse(null, 'Report link deleted successfully');
    }
}
