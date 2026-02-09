<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\NotificationPreferenceResource;
use App\Models\UserNotificationPreference;
use App\Services\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class NotificationPreferenceController extends ApiController
{
    #[OA\Get(
        path: '/api/v1/notifications/preferences',
        summary: 'Get notification preferences',
        description: 'Get the authenticated user\'s notification preferences',
        operationId: 'getNotificationPreferences',
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Preferences retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Notification preferences retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/NotificationPreference'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get or create default preferences
        $preferences = $user->notificationPreferences ?? $this->createDefaultPreferences($user);

        return $this->successResponse(
            new NotificationPreferenceResource($preferences),
            'Notification preferences retrieved successfully'
        );
    }

    #[OA\Put(
        path: '/api/v1/notifications/preferences',
        summary: 'Update notification preferences',
        description: 'Update the authenticated user\'s notification preferences',
        operationId: 'updateNotificationPreferences',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'email_enabled',
                        type: 'boolean',
                        description: 'Enable or disable email notifications',
                        example: true
                    ),
                    new OA\Property(
                        property: 'notification_types',
                        type: 'object',
                        description: 'Enable or disable specific notification types',
                        properties: [
                            new OA\Property(property: 'comment', type: 'boolean', example: true),
                            new OA\Property(property: 'report_status', type: 'boolean', example: true),
                            new OA\Property(property: 'proposal_status', type: 'boolean', example: false),
                            new OA\Property(property: 'announcement', type: 'boolean', example: true),
                            new OA\Property(property: 'system', type: 'boolean', example: true),
                        ]
                    ),
                ]
            )
        ),
        tags: ['Notifications'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Preferences updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Notification preferences updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/NotificationPreference'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['email_enabled' => ['The email enabled field must be a boolean.']]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email_enabled' => 'nullable|boolean',
            'notification_types' => 'nullable|array',
            'notification_types.comment' => 'nullable|boolean',
            'notification_types.report_status' => 'nullable|boolean',
            'notification_types.proposal_status' => 'nullable|boolean',
            'notification_types.announcement' => 'nullable|boolean',
            'notification_types.system' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = $request->user();

        // Get or create preferences
        $preferences = $user->notificationPreferences ?? $this->createDefaultPreferences($user);

        $oldValues = $preferences->toArray();

        // Update only provided fields
        $updateData = [];
        if ($request->has('email_enabled')) {
            $updateData['email_enabled'] = $request->input('email_enabled');
        }
        if ($request->has('notification_types')) {
            // Merge with existing types to preserve unspecified types
            $existingTypes = $preferences->notification_types ?? [];
            $newTypes = $request->input('notification_types', []);
            $updateData['notification_types'] = array_merge($existingTypes, $newTypes);
        }

        $preferences->update($updateData);

        // Log activity
        ActivityLog::log(
            'updated',
            $preferences,
            $oldValues,
            $preferences->fresh()->toArray()
        );

        return $this->successResponse(
            new NotificationPreferenceResource($preferences->fresh()),
            'Notification preferences updated successfully'
        );
    }

    /**
     * Create default notification preferences for a user.
     */
    private function createDefaultPreferences($user): UserNotificationPreference
    {
        return UserNotificationPreference::create([
            'user_id' => $user->id,
            'email_enabled' => true,
            'notification_types' => [
                'comment' => true,
                'report_status' => true,
                'proposal_status' => true,
                'announcement' => true,
                'system' => true,
            ],
        ]);
    }
}
