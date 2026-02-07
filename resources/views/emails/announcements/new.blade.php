<x-mail::message>
# {{ $announcement->priority === 'urgent' ? '[URGENT] ' : '' }}New Announcement

Hello {{ $recipient->first_name }},

A new announcement has been posted:

## {{ $announcement->title }}

<x-mail::panel>
{!! nl2br(e($announcement->content)) !!}
</x-mail::panel>

@if($announcement->expires_at)
**This announcement expires on:** {{ $announcement->expires_at->format('F j, Y') }}
@endif

<x-mail::button :url="config('app.url')">
View Announcement
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
