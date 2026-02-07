<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'priority' => $this->priority,
            'priority_badge_class' => $this->getPriorityBadgeClass(),
            'target_type' => $this->target_type,
            'is_pinned' => $this->is_pinned,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_scheduled' => $this->isScheduled(),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'departments' => DepartmentResource::collection($this->whenLoaded('departments')),
            'targeted_user_ids' => $this->when(
                $this->relationLoaded('users') && $this->target_type === 'users',
                fn () => $this->users->pluck('id')
            ),
            'read_at' => $this->when(
                $user !== null,
                fn () => $this->getReadAtForUser($user)
            ),
            'is_read' => $this->when(
                $user !== null,
                fn () => $this->isReadBy($user)
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Get read_at timestamp for the current user.
     */
    private function getReadAtForUser($user): ?string
    {
        if (!$user) {
            return null;
        }

        $pivot = $this->users()
            ->wherePivot('user_id', $user->id)
            ->first();

        return $pivot?->pivot?->read_at?->toIso8601String();
    }
}
