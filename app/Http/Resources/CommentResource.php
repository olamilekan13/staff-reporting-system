<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'user' => new UserResource($this->whenLoaded('user')),
            'parent_id' => $this->parent_id,
            'replies' => $this->when(
                $this->relationLoaded('replies') || $this->relationLoaded('allReplies'),
                fn () => CommentResource::collection(
                    $this->relationLoaded('allReplies') ? $this->allReplies : $this->replies
                )
            ),
            'replies_count' => $this->when(
                $this->relationLoaded('replies') || $this->relationLoaded('allReplies'),
                fn () => $this->relationLoaded('allReplies')
                    ? $this->allReplies->count()
                    : $this->replies->count()
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
