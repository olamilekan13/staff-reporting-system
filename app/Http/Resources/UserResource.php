<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kingschat_id' => $this->kingschat_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'masked_phone' => $this->masked_phone,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->toArray()),
            'permissions' => $this->whenLoaded('permissions', fn () => $this->getAllPermissions()->pluck('name')->toArray()),
            'profile_photo_url' => $this->getProfilePhotoUrl(),
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    protected function getProfilePhotoUrl(): ?string
    {
        $media = $this->getFirstMedia('profile_photo');

        return $media?->getUrl();
    }
}
