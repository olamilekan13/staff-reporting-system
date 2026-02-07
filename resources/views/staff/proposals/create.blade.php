@extends('layouts.app')

@section('title', 'Create Proposal')
@section('page-title', 'Create Proposal')

@section('content')
    <div class="max-w-3xl">
        {{-- Back link --}}
        <a href="{{ route('staff.proposals.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Proposals
        </a>

        <x-card title="New Proposal">
            <form method="POST" action="{{ route('staff.proposals.store') }}" enctype="multipart/form-data" id="proposal-form">
                @csrf

                <div class="space-y-5">
                    <x-input name="title" label="Title" :value="old('title')" :error="$errors->first('title')" required placeholder="Enter proposal title" />

                    <div>
                        <label for="description" class="label">Description</label>
                        <div id="editor">{!! old('description') !!}</div>
                        <input type="hidden" name="description" id="description-input" value="{{ old('description') }}">
                        @error('description')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-file-upload name="file" label="Attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif" :maxSize="10485760" :error="$errors->first('file')" />
                </div>

                <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                    <x-button type="submit" variant="primary">Submit Proposal</x-button>
                    <a href="{{ route('staff.proposals.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
                </div>
            </form>
        </x-card>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css" />
<style>
    .ck-editor__editable {
        min-height: 200px;
    }
</style>
@endpush

@push('scripts')
<script type="importmap">
{
    "imports": {
        "ckeditor5": "https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.js",
        "ckeditor5/": "https://cdn.ckeditor.com/ckeditor5/43.3.1/"
    }
}
</script>
<script type="module">
import { ClassicEditor, Essentials, Bold, Italic, Heading, Link, List, Paragraph, BlockQuote, Table, Indent } from 'ckeditor5';

const editor = await ClassicEditor.create(document.querySelector('#editor'), {
    plugins: [Essentials, Bold, Italic, Heading, Link, List, Paragraph, BlockQuote, Table, Indent],
    toolbar: ['heading', '|', 'bold', 'italic', 'link', '|', 'bulletedList', 'numberedList', '|', 'blockQuote', 'insertTable', '|', 'undo', 'redo'],
});

document.getElementById('proposal-form').addEventListener('submit', function () {
    document.getElementById('description-input').value = editor.getData();
});
</script>
@endpush
