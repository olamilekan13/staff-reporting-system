<x-mail::message>
# New Proposal Created

Hello Admin,

A new proposal has been submitted in the system.

**Proposal Details:**
- **Title:** {{ $proposal->title }}
- **Author:** {{ $author->full_name }}
- **Status:** {{ ucfirst(str_replace('_', ' ', $proposal->status)) }}

@if($proposal->description)
**Description:**
<x-mail::panel>
{{ Str::limit($proposal->description, 300) }}
</x-mail::panel>
@endif

<x-mail::button :url="config('app.url') . '/proposals/' . $proposal->id">
View Proposal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
