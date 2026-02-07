<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\SettingResource;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class SettingController extends ApiController
{
    #[OA\Get(
        path: '/api/v1/settings',
        summary: 'List all settings',
        description: 'Get all settings grouped by category. Admin and super admin only.',
        operationId: 'listSettings',
        tags: ['Settings'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Settings retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'general', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                                new OA\Property(property: 'appearance', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                                new OA\Property(property: 'email', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                                new OA\Property(property: 'reports', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                                new OA\Property(property: 'features', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', SiteSetting::class);

        $settings = SiteSetting::all();

        $grouped = $settings->groupBy('group')->map(function ($group) {
            return SettingResource::collection($group);
        });

        return $this->successResponse($grouped, 'Settings retrieved successfully');
    }

    #[OA\Get(
        path: '/api/v1/settings/{group}',
        summary: 'Get settings by group',
        description: 'Get settings for a specific group. Admin and super admin only.',
        operationId: 'getSettingsByGroup',
        tags: ['Settings'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'group',
                in: 'path',
                required: true,
                description: 'Settings group name',
                schema: new OA\Schema(type: 'string', enum: ['general', 'appearance', 'email', 'reports', 'features'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Settings retrieved successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Invalid group', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function show(string $group): JsonResponse
    {
        Gate::authorize('viewAny', SiteSetting::class);

        $validGroups = [
            SiteSetting::GROUP_GENERAL,
            SiteSetting::GROUP_APPEARANCE,
            SiteSetting::GROUP_EMAIL,
            SiteSetting::GROUP_REPORTS,
            SiteSetting::GROUP_FEATURES,
        ];

        if (!in_array($group, $validGroups)) {
            return $this->validationErrorResponse([
                'group' => ['Invalid group. Must be one of: ' . implode(', ', $validGroups)],
            ]);
        }

        $settings = SiteSetting::byGroup($group)->get();

        return $this->successResponse(
            SettingResource::collection($settings),
            'Settings retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/api/v1/settings',
        summary: 'Update settings',
        description: 'Update multiple settings at once. Super admin only. Pass key-value pairs in the request body.',
        operationId: 'updateSettings',
        tags: ['Settings'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Key-value pairs of settings to update',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'settings', type: 'object', example: '{"site_name": "My App", "primary_color": "#ff0000", "enable_proposals": true}', description: 'Object of setting key-value pairs'),
                ],
                required: ['settings']
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Settings updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Settings updated successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'general', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                                new OA\Property(property: 'appearance', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                                new OA\Property(property: 'email', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                                new OA\Property(property: 'reports', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                                new OA\Property(property: 'features', type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Super admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function update(Request $request): JsonResponse
    {
        Gate::authorize('manage', SiteSetting::class);

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $settings = $request->input('settings');
        $existingKeys = SiteSetting::pluck('key')->toArray();
        $invalidKeys = array_diff(array_keys($settings), $existingKeys);

        if (!empty($invalidKeys)) {
            return $this->validationErrorResponse([
                'settings' => ['Invalid setting keys: ' . implode(', ', $invalidKeys)],
            ]);
        }

        $oldValues = [];
        $newValues = [];

        foreach ($settings as $key => $value) {
            $setting = SiteSetting::where('key', $key)->first();
            if ($setting) {
                $oldValues[$key] = $setting->getRawOriginal('value');
                SiteSetting::set($key, $value);
                $newValues[$key] = $value;
            }
        }

        ActivityLog::log(ActivityLog::ACTION_UPDATE, null, $oldValues, $newValues);

        // Return all settings grouped
        $allSettings = SiteSetting::all()->groupBy('group')->map(function ($group) {
            return SettingResource::collection($group);
        });

        return $this->successResponse($allSettings, 'Settings updated successfully');
    }

    #[OA\Post(
        path: '/api/v1/settings/logo',
        summary: 'Upload site logo',
        description: 'Upload a new site logo image. Super admin only.',
        operationId: 'uploadLogo',
        tags: ['Settings'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['logo'],
                    properties: [
                        new OA\Property(property: 'logo', type: 'string', format: 'binary', description: 'Logo image file (jpg, jpeg, png, svg). Max 2MB.'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logo uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logo uploaded successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'url', type: 'string', example: '/storage/branding/logo.png'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Super admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function uploadLogo(Request $request): JsonResponse
    {
        Gate::authorize('manage', SiteSetting::class);

        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpg,jpeg,png,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $url = $this->storeImage($request->file('logo'), 'site_logo');

        return $this->successResponse(['url' => $url], 'Logo uploaded successfully');
    }

    #[OA\Post(
        path: '/api/v1/settings/favicon',
        summary: 'Upload favicon',
        description: 'Upload a new favicon. Super admin only.',
        operationId: 'uploadFavicon',
        tags: ['Settings'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['favicon'],
                    properties: [
                        new OA\Property(property: 'favicon', type: 'string', format: 'binary', description: 'Favicon file (ico, png, svg). Max 1MB.'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Favicon uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Favicon uploaded successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'url', type: 'string', example: '/storage/branding/favicon.ico'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Forbidden - Super admin only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function uploadFavicon(Request $request): JsonResponse
    {
        Gate::authorize('manage', SiteSetting::class);

        $validator = Validator::make($request->all(), [
            'favicon' => 'required|file|mimes:ico,png,svg|max:1024',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $url = $this->storeImage($request->file('favicon'), 'site_favicon');

        return $this->successResponse(['url' => $url], 'Favicon uploaded successfully');
    }

    /**
     * Store an uploaded image and update the corresponding setting.
     */
    private function storeImage($file, string $settingKey): string
    {
        // Delete old file if exists
        $oldValue = SiteSetting::where('key', $settingKey)->value('value');
        if ($oldValue && Storage::disk('public')->exists($oldValue)) {
            Storage::disk('public')->delete($oldValue);
        }

        $path = $file->store('branding', 'public');
        SiteSetting::set($settingKey, $path);

        return Storage::disk('public')->url($path);
    }
}
