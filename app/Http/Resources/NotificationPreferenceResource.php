<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'NotificationPreference',
    title: 'Notification Preference',
    description: 'User notification preference settings',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'email_enabled', type: 'boolean', example: true),
        new OA\Property(
            property: 'notification_types',
            type: 'object',
            properties: [
                new OA\Property(property: 'comment', type: 'boolean', example: true),
                new OA\Property(property: 'report_status', type: 'boolean', example: true),
                new OA\Property(property: 'proposal_status', type: 'boolean', example: false),
                new OA\Property(property: 'announcement', type: 'boolean', example: true),
                new OA\Property(property: 'system', type: 'boolean', example: true),
            ]
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-02-08T14:30:00Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-02-08T14:30:00Z'),
    ]
)]
class NotificationPreferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'email_enabled' => $this->email_enabled,
            'notification_types' => [
                'comment' => $this->notification_types['comment'] ?? true,
                'report_status' => $this->notification_types['report_status'] ?? true,
                'proposal_status' => $this->notification_types['proposal_status'] ?? true,
                'announcement' => $this->notification_types['announcement'] ?? true,
                'system' => $this->notification_types['system'] ?? true,
            ],
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
