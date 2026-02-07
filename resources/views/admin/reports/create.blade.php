@extends('layouts.app')

@section('title', 'Create Report')
@section('page-title', 'Create Report')

@section('content')
    <div class="max-w-3xl">
        {{-- Back link --}}
        <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Reports
        </a>

        <x-card title="New Report">
            <form method="POST" action="{{ route('admin.reports.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="space-y-5">
                    <x-input name="title" label="Title" :value="old('title')" :error="$errors->first('title')" required placeholder="Enter report title" />

                    <x-select name="report_type" label="Report Type" :selected="old('report_type', $type)" :error="$errors->first('report_type')" required
                        :options="['personal' => 'Personal', 'department' => 'Department']" />

                    <x-select name="report_category" label="Category" :selected="old('report_category', $category)" :error="$errors->first('report_category')" required
                        :options="['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annual' => 'Annual']" />

                    <div>
                        <label for="description" class="label">Description</label>
                        <textarea name="description" id="description">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-file-upload name="file" label="Attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.mp4,.mov,.avi" :maxSize="10485760" :error="$errors->first('file')" />
                </div>

                <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                    <x-button type="submit" name="action" value="draft" variant="secondary">Save as Draft</x-button>
                    <x-button type="submit" name="action" value="submit" variant="primary">Submit Report</x-button>
                    <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
                </div>
            </form>
        </x-card>
    </div>
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
