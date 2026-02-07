<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AuthUserResource extends UserResource
{
    public static ?string $authToken = null;

    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'unread_notifications_count' => $this->notifications()->unread()->count(),
            'token' => $this->when(static::$authToken !== null, static::$authToken),
        ]);
    }

    public static function withToken(mixed $resource, string $token): self
    {
        static::$authToken = $token;
        $instance = new static($resource);
        static::$authToken = null;

        return $instance;
    }
}
