<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use OpenApi\Attributes as OA;

class HealthController extends ApiController
{
    #[OA\Get(
        path: '/api/v1/health',
        summary: 'API Health Check',
        description: 'Check if the API is running and healthy',
        operationId: 'healthCheck',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API is healthy',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'API is running'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'healthy'),
                                new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                                new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function __invoke()
    {
        return $this->successResponse([
            'status' => 'healthy',
            'version' => config('api.version', 'v1'),
            'timestamp' => now()->toISOString(),
        ], 'API is running');
    }
}
