<x-mail::message>
# New Comment on Your {{ $resourceType }}

Hello {{ $recipient->first_name }},

**{{ $commenter->full_name }}** has commented on your {{ strtolower($resourceType) }} "**{{ $resourceTitle }}**":

<x-mail::panel>
{{ Str::limit($comment->content, 300) }}
</x-mail::panel>

<x-mail::button :url="config('app.url')">
View {{ $resourceType }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
