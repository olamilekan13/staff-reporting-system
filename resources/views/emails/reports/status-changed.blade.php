<x-mail::message>
# Report Status Updated

Hello {{ $recipient->first_name }},

Your report "**{{ $report->title }}**" has been updated.

<x-mail::panel>
**Status:** {{ ucfirst($report->status) }}

@if($report->review_notes)
**Review Notes:** {{ $report->review_notes }}
@endif

@if($reviewer)
**Reviewed by:** {{ $reviewer->full_name }}
@endif
</x-mail::panel>

<x-mail::button :url="config('app.url')">
View Report
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
