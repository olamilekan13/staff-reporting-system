@extends('layouts.app')

@section('title', 'Edit Report')
@section('page-title', 'Edit Report')

@section('content')
    {{-- Back link --}}
    <a href="{{ route('hod.reports.show', $report) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to Report
    </a>

    <x-card title="Edit Report">
        <form method="POST" action="{{ route('hod.reports.update', $report) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <x-input name="title" label="Title" :value="old('title', $report->title)" :error="$errors->first('title')" required placeholder="Enter report title" />

                <x-select name="report_type" label="Report Type" :selected="old('report_type', $report->report_type)" :error="$errors->first('report_type')" required
                    :options="['personal' => 'Personal', 'department' => 'Department']" />

                <x-select name="report_category" label="Category" :selected="old('report_category', $report->report_category)" :error="$errors->first('report_category')" required
                    :options="['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annual' => 'Annual']" />

                <div>
                    <label for="description" class="label">Description</label>
                    <textarea name="description" id="description">{{ old('description', $report->description) }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Current file --}}
                @if($report->file_name)
                    <div>
                        <label class="label">Current File</label>
                        <div class="flex items-center gap-3 bg-gray-50 rounded-lg px-4 py-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <span class="text-sm text-gray-700 flex-1">{{ $report->file_name }}</span>
                            <span class="text-xs text-gray-400">{{ $report->getFormattedFileSize() }}</span>
                        </div>
                    </div>
                @endif

                <x-file-upload name="file" label="{{ $report->file_name ? 'Replace File' : 'Attachment' }}" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.mp4,.mov,.avi" :maxSize="10485760" :error="$errors->first('file')" />
            </div>

            <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                <x-button type="submit" name="action" value="draft" variant="secondary">Save Draft</x-button>
                <x-button type="submit" name="action" value="submit" variant="primary">Submit Report</x-button>
                <a href="{{ route('hod.reports.show', $report) }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    ClassicEditor.create(document.querySelector('#description'), {
        toolbar: ['heading', '|', 'bold', 'italic', 'underline', '|', 'bulletedList', 'numberedList', '|', 'link', 'blockQuote', '|', 'undo', 'redo'],
        placeholder: 'Describe the report content...',
    }).catch(error => console.error(error));
</script>
@endpush
