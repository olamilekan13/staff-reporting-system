<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends ApiController
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    #[OA\Get(
        path: '/api/v1/reports',
        summary: 'List reports',
        description: 'Get paginated list of reports filtered by user role and optional filters',
        operationId: 'listReports',
        tags: ['Reports'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', description: 'Filter by category', schema: new OA\Schema(type: 'string', enum: ['daily', 'weekly', 'monthly', 'quarterly', 'annual'])),
            new OA\Parameter(name: 'type', in: 'query', description: 'Filter by type', schema: new OA\Schema(type: 'string', enum: ['personal', 'department'])),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['draft', 'submitted', 'reviewed', 'approved', 'rejected'])),
            new OA\Parameter(name: 'department_id', in: 'query', description: 'Filter by department', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from_date', in: 'query', description: 'Filter from date (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to_date', in: 'query', description: 'Filter to date (Y-m-d)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'search', in: 'query', description: 'Search in title and description', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reports retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Reports retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Report')),
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
        Gate::authorize('viewAny', Report::class);

        $filters = $request->only([
            'category',
            'type',
            'status',
            'department_id',
            'from_date',
            'to_date',
            'search',
            'per_page',
        ]);

        $reports = $this->reportService->getReportsForUser($request->user(), $filters);

        return $this->paginatedResponse(
            ReportResource::collection($reports),
            'Reports retrieved successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/reports',
        summary: 'Create a report',
        description: 'Create a new report with optional file attachment',
        operationId: 'createReport',
        tags: ['Reports'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['title'],
                    properties: [
                        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Weekly Status Report'),
                        new OA\Property(property: 'description', type: 'string', maxLength: 5000, example: 'Summary of this week activities'),
                        new OA\Property(property: 'report_type', type: 'string', enum: ['personal', 'department'], example: 'personal'),
                        new OA\Property(property: 'report_category', type: 'string', enum: ['daily', 'weekly', 'monthly', 'quarterly', 'annual'], example: 'weekly'),
                        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'File upload (max 50MB)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Report created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Report created successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Report'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Report::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'report_type' => 'nullable|in:personal,department',
            'report_category' => 'nullable|in:daily,weekly,monthly,quarterly,annual',
            'file' => 'nullable|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,mp4,mov,avi',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $report = $this->reportService->createReport(
            $request->user(),
            $validator->validated(),
            $request->file('file')
        );

        return $this->createdResponse(
            new ReportResource($report),
            'Report created successfully'
        );
    }

    #[OA\Get(
        path: '/api/v1/reports/{report}',
        summary: 'Get a report',
        description: 'Get a single report with comments',
        operationId: 'getReport',
        tags: ['Reports'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'report', in: 'path', required: true, description: 'Report ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Report retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Report retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Report'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Report not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show(Report $report): JsonResponse
    {
        Gate::authorize('view', $report);

        $report->load(['user', 'department', 'reviewer', 'comments.user', 'comments.replies.user']);

        return $this->successResponse(
            new ReportResource($report),
            'Report retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/api/v1/reports/{report}',
        summary: 'Update a report',
        description: 'Update a report (only if status is draft)',
        operationId: 'updateReport',
        tags: ['Reports'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'report', in: 'path', required: true, description: 'Report ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'title', type: 'string', maxLength: 255),
                        new OA\Property(property: 'description', type: 'string', maxLength: 5000),
                        new OA\Property(property: 'report_type', type: 'string', enum: ['personal', 'department']),
                        new OA\Property(property: 'report_category', type: 'string', enum: ['daily', 'weekly', 'monthly', 'quarterly', 'annual']),
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Report updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Report updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Report'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - can only update draft reports', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Report not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(Request $request, Report $report): JsonResponse
    {
        Gate::authorize('update', $report);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'report_type' => 'nullable|in:personal,department',
            'report_category' => 'nullable|in:daily,weekly,monthly,quarterly,annual',
            'file' => 'nullable|file|max:51200|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,mp4,mov,avi',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $report = $this->reportService->updateReport(
            $report,
            $validator->validated(),
            $request->file('file')
        );

        return $this->successResponse(
            new ReportResource($report),
            'Report updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/v1/reports/{report}',
        summary: 'Delete a report',
        description: 'Soft delete a report (owner or admin only)',
        operationId: 'deleteReport',
        tags: ['Reports'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'report', in: 'path', required: true, description: 'Report ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Report deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Report deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Report not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function destroy(Report $report): JsonResponse
    {
        Gate::authorize('delete', $report);

        $this->reportService->deleteReport($report);

        return $this->successResponse(null, 'Report deleted successfully');
    }

    #[OA\Post(
        path: '/api/v1/reports/{report}/submit',
        summary: 'Submit a report',
        description: 'Submit a draft report for review',
        operationId: 'submitReport',
        tags: ['Reports'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'report', in: 'path', required: true, description: 'Report ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Report submitted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Report submitted successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Report'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - can only submit own draft reports', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Report not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function submit(Report $report): JsonResponse
    {
        Gate::authorize('submit', $report);

        $report = $this->reportService->submitReport($report);

        return $this->successResponse(
            new ReportResource($report),
            'Report submitted successfully'
        );
    }

    #[OA\Post(
        path: '/api/v1/reports/{report}/review',
        summary: 'Review a report',
        description: 'Approve or reject a submitted report (Admin/HOD only)',
        operationId: 'reviewReport',
        tags: ['Reports'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'report', in: 'path', required: true, description: 'Report ID', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['approved', 'rejected'], example: 'approved'),
                    new OA\Property(property: 'notes', type: 'string', maxLength: 2000, example: 'Great work on this report!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Report reviewed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Report reviewed successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Report'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - only Admin/HOD can review', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Report not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function review(Request $request, Report $report): JsonResponse
    {
        Gate::authorize('review', $report);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $report = $this->reportService->reviewReport(
            $report,
            $request->user(),
            $request->status,
            $request->notes
        );

        return $this->successResponse(
            new ReportResource($report),
            'Report reviewed successfully'
        );
    }

    #[OA\Get(
        path: '/api/v1/reports/{report}/download',
        summary: 'Download report file',
        description: 'Download the file attached to a report',
        operationId: 'downloadReport',
        tags: ['Reports'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'report', in: 'path', required: true, description: 'Report ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File download stream', content: new OA\MediaType(mediaType: 'application/octet-stream')),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'File not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function download(Report $report): StreamedResponse|JsonResponse
    {
        Gate::authorize('download', $report);

        $stream = $this->reportService->getFileStream($report);

        if (!$stream) {
            return $this->notFoundResponse('File not found');
        }

        return $stream;
    }
}
