<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AuthUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AuthController extends ApiController
{
    #[OA\Post(
        path: '/api/v1/auth/verify-kingschat',
        summary: 'Verify KingsChat ID',
        description: 'Verify if a user exists by their KingsChat ID and return masked phone for verification',
        operationId: 'verifyKingschat',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kingschat_id'],
                properties: [
                    new OA\Property(property: 'kingschat_id', type: 'string', example: 'john.doe'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'masked_phone', type: 'string', example: '****5678'),
                                new OA\Property(property: 'user_name', type: 'string', example: 'John'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'User not found or inactive', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function verifyKingschat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kingschat_id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = User::where('kingschat_id', $request->kingschat_id)
            ->active()
            ->first();

        if (!$user) {
            return $this->notFoundResponse('User not found or inactive');
        }

        return $this->successResponse([
            'masked_phone' => $user->masked_phone,
            'user_name' => $user->first_name,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Login user',
        description: 'Authenticate user with KingsChat ID and last 4 digits of phone. Returns token for mobile or starts session for web.',
        operationId: 'login',
        tags: ['Authentication'],
        parameters: [
            new OA\Parameter(
                name: 'X-Client-Type',
                in: 'header',
                description: 'Client type (mobile or web)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['mobile', 'web'], default: 'mobile')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kingschat_id', 'phone'],
                properties: [
                    new OA\Property(property: 'kingschat_id', type: 'string', example: 'john.doe'),
                    new OA\Property(property: 'phone', type: 'string', minLength: 4, maxLength: 4, example: '5678'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'iPhone 15 Pro'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: '1|abc123...'),
                                new OA\Property(property: 'user', ref: '#/components/schemas/AuthUser'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Phone verification failed', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'User not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kingschat_id' => 'required|string|max:255',
            'phone' => 'required|string|size:4',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = User::where('kingschat_id', $request->kingschat_id)
            ->active()
            ->first();

        if (!$user) {
            return $this->notFoundResponse('User not found or inactive');
        }

        // Validate phone matches last 4 digits
        $phoneLast4 = substr($user->phone, -4);
        if ($request->phone !== $phoneLast4) {
            return $this->unauthorizedResponse('Phone verification failed');
        }

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // Load relationships for response
        $user->load(['department', 'roles', 'permissions']);

        // Determine client type from header
        $clientType = $request->header('X-Client-Type', 'mobile');

        if ($clientType === 'web') {
            // Session-based auth for web
            Auth::login($user);
            $request->session()->regenerate();

            return $this->successResponse([
                'user' => new AuthUserResource($user),
            ], 'Login successful');
        }

        // Token-based auth for mobile
        $deviceName = $request->device_name ?? 'mobile-device';
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user' => new AuthUserResource($user),
        ], 'Login successful');
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Logout user',
        description: 'Revokes current token for mobile or destroys session for web',
        operationId: 'logout',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'X-Client-Type',
                in: 'header',
                description: 'Client type (mobile or web)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['mobile', 'web'], default: 'mobile')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $clientType = $request->header('X-Client-Type', 'mobile');

        if ($clientType === 'web') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } else {
            // Revoke current token for mobile
            $user->currentAccessToken()->delete();
        }

        return $this->successResponse(null, 'Logged out successfully');
    }

    #[OA\Get(
        path: '/api/v1/auth/user',
        summary: 'Get authenticated user',
        description: 'Returns the currently authenticated user with roles, permissions, and department',
        operationId: 'getAuthUser',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'user', ref: '#/components/schemas/AuthUser'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $user->load(['department', 'roles', 'permissions']);

        return $this->successResponse([
            'user' => new AuthUserResource($user),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/auth/profile',
        summary: 'Update user profile',
        description: 'Update authenticated user profile (email and profile photo only)',
        operationId: 'updateProfile',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                        new OA\Property(property: 'profile_photo', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Profile updated successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'user', ref: '#/components/schemas/AuthUser'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // Update email if provided
        if ($request->has('email')) {
            $user->email = $request->email;
        }

        // Handle profile photo upload via MediaLibrary
        if ($request->hasFile('profile_photo')) {
            $user->clearMediaCollection('profile_photo');
            $user->addMediaFromRequest('profile_photo')
                ->toMediaCollection('profile_photo');
        }

        $user->save();
        $user->load(['department', 'roles', 'permissions']);

        return $this->successResponse([
            'user' => new AuthUserResource($user),
        ], 'Profile updated successfully');
    }
}
