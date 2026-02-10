<x-mail::message>
# New Comment on {{ $resourceType }}

Hello Admin,

A new comment has been posted on a {{ strtolower($resourceType) }} in the system.

**Comment Details:**
- **{{ $resourceType }}:** {{ $resourceTitle }}
- **Author:** {{ $resource->user->full_name }}
- **Commenter:** {{ $commenter->full_name }}

**Comment:**
<x-mail::panel>
{{ Str::limit($comment->content, 300) }}
</x-mail::panel>

<x-mail::button :url="config('app.url') . '/' . strtolower($resourceType) . 's/' . $resource->id">
View {{ $resourceType }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
