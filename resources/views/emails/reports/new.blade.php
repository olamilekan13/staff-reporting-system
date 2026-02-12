<x-mail::message>
# New Report Created

Hello Admin,

A new report has been created in the system.

**Report Details:**
- **Title:** {{ $report->title }}
- **Author:** {{ $author->full_name }}
- **Department:** {{ $department ? $department->name : 'N/A' }}
- **Category:** {{ ucfirst(str_replace('_', ' ', $report->report_category)) }}
- **Type:** {{ ucfirst(str_replace('_', ' ', $report->report_type)) }}
- **Status:** {{ ucfirst($report->status) }}

@if($report->description)
**Description:**
<x-mail::panel>
{{ Str::limit($report->description, 300) }}
</x-mail::panel>
@endif

<x-mail::button :url="config('app.url') . '/admin/reports/' . $report->id">
View Report
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
