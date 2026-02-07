<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ProposalResource;
use App\Models\Proposal;
use App\Services\ProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProposalController extends ApiController
{
    public function __construct(
        protected ProposalService $proposalService
    ) {}

    #[OA\Get(
        path: '/api/v1/proposals',
        summary: 'List proposals',
        description: 'Get paginated list of proposals filtered by user role and optional filters',
        operationId: 'listProposals',
        tags: ['Proposals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['pending', 'under_review', 'approved', 'rejected'])),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search in title and description', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proposals retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Proposals retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Proposal')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Proposal::class);

        $filters = $request->only([
            'status',
            'search',
            'per_page',
        ]);

        $proposals = $this->proposalService->getProposalsForUser($request->user(), $filters);

        return $this->paginatedResponse(
            ProposalResource::collection($proposals),
            'Proposals retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/proposals',
        summary: 'Create a proposal',
        description: 'Create a new proposal with optional file attachment',
        operationId: 'createProposal',
        tags: ['Proposals'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['title'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'New Project Proposal'),
                        new OA\Property(property: 'description', type: 'string', maxLength: 5000, example: 'Description of the proposed project'),
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'File upload (max 50MB)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Proposal created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Proposal created successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Proposal'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Proposal::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $proposal = $this->proposalService->createProposal(
            $request->user(),
            $validator->validated(),
            $request->file('file')
        );

        return $this->createdResponse(
            new ProposalResource($proposal),
            'Proposal created successfully'
        );
    }

    #[OA\Get(
        path: '/api/v1/proposals/{proposal}',
        summary: 'Get a proposal',
        description: 'Get a single proposal with comments',
        operationId: 'getProposal',
        tags: ['Proposals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'proposal', in: 'path', required: true, description: 'Proposal ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proposal retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Proposal retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Proposal'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Proposal not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Proposal $proposal): JsonResponse
    {
        Gate::authorize('view', $proposal);

        $proposal->load(['user', 'reviewer', 'comments.user', 'comments.replies.user']);

        return $this->successResponse(
            new ProposalResource($proposal),
            'Proposal retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/api/v1/proposals/{proposal}',
        summary: 'Update a proposal',
        description: 'Update a proposal (only if status is pending)',
        operationId: 'updateProposal',
        tags: ['Proposals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'proposal', in: 'path', required: true, description: 'Proposal ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'title', type: 'string', maxLength: 255),
                        new OA\Property(property: 'description', type: 'string', maxLength: 5000),
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proposal updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Proposal updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Proposal'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - can only update pending proposals', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Proposal not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(Request $request, Proposal $proposal): JsonResponse
    {
        Gate::authorize('update', $proposal);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $proposal = $this->proposalService->updateProposal(
            $proposal,
            $validator->validated(),
            $request->file('file')
        );

        return $this->successResponse(
            new ProposalResource($proposal),
            'Proposal updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/v1/proposals/{proposal}',
        summary: 'Delete a proposal',
        description: 'Soft delete a proposal (only if status is pending or rejected)',
        operationId: 'deleteProposal',
        tags: ['Proposals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'proposal', in: 'path', required: true, description: 'Proposal ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proposal deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Proposal deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - can only delete pending or rejected proposals', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Proposal not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Proposal $proposal): JsonResponse
    {
        Gate::authorize('delete', $proposal);

        $this->proposalService->deleteProposal($proposal);

        return $this->successResponse(null, 'Proposal deleted successfully');
    }

    #[OA\Post(
        path: '/api/v1/proposals/{proposal}/review',
        summary: 'Review a proposal',
        description: 'Set proposal status to under_review, approved, or rejected (Admin/Head of Operations only)',
        operationId: 'reviewProposal',
        tags: ['Proposals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'proposal', in: 'path', required: true, description: 'Proposal ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['under_review', 'approved', 'rejected'], example: 'approved'),
                    new OA\Property(property: 'admin_notes', type: 'string', maxLength: 2000, example: 'This proposal has been approved.'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proposal reviewed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Proposal reviewed successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Proposal'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - only Admin/Head of Operations can review', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Proposal not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function review(Request $request, Proposal $proposal): JsonResponse
    {
        Gate::authorize('review', $proposal);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:under_review,approved,rejected',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $proposal = $this->proposalService->reviewProposal(
            $proposal,
            $request->user(),
            $request->status,
            $request->admin_notes
        );

        return $this->successResponse(
            new ProposalResource($proposal),
            'Proposal reviewed successfully'
        );
    }

    #[OA\Get(
        path: '/api/v1/proposals/{proposal}/download',
        summary: 'Download proposal file',
        description: 'Download the file attached to a proposal',
        operationId: 'downloadProposal',
        tags: ['Proposals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'proposal', in: 'path', required: true, description: 'Proposal ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File download stream', content: new OA\MediaType(mediaType: 'application/octet-stream')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'File not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function download(Proposal $proposal): StreamedResponse|JsonResponse
    {
        Gate::authorize('download', $proposal);

        $stream = $this->proposalService->getFileStream($proposal);

        if (!$stream) {
            return $this->notFoundResponse('File not found');
        }

        return $stream;
    }
}
