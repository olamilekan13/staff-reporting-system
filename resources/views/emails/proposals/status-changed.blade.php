<x-mail::message>
# Proposal Status Updated

Hello {{ $recipient->first_name }},

Your proposal "**{{ $proposal->title }}**" has been updated.

<x-mail::panel>
**Status:** {{ ucfirst(str_replace('_', ' ', $proposal->status)) }}

@if($proposal->admin_notes)
**Admin Notes:** {{ $proposal->admin_notes }}
@endif

@if($reviewer)
**Reviewed by:** {{ $reviewer->full_name }}
@endif
</x-mail::panel>

<x-mail::button :url="config('app.url')">
View Proposal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
