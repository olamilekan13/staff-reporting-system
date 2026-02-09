@extends('layouts.app')

@section('title', 'Create Proposal')
@section('page-title', 'Create Proposal')

@section('content')
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

                    <x-file-upload name="file" label="Attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.mp4,.mov,.avi" :maxSize="10485760" :error="$errors->first('file')" />
                </div>

                <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                    <x-button type="submit" variant="primary" id="submit-button">
                        <span class="button-text">Submit Proposal</span>
                        <span class="button-loading hidden">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submitting...
                        </span>
                    </x-button>
                    <a href="{{ route('staff.proposals.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
                </div>
            </form>
        </x-card>
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

document.getElementById('proposal-form').addEventListener('submit', function (e) {
    document.getElementById('description-input').value = editor.getData();

    const fileInput = this.querySelector('input[type="file"]');
    const submitButton = document.getElementById('submit-button');
    const buttonText = submitButton.querySelector('.button-text');
    const buttonLoading = submitButton.querySelector('.button-loading');

    // If no file is selected, just submit normally without AJAX
    if (!fileInput || !fileInput.files.length) {
        console.log('No file selected, submitting form normally');
        // Show loading state
        submitButton.disabled = true;
        buttonText.classList.add('hidden');
        buttonLoading.classList.remove('hidden');
        return; // Let form submit normally
    }

    console.log('File selected, using AJAX upload');
    e.preventDefault();

    // Show loading state
    submitButton.disabled = true;
    buttonText.classList.add('hidden');
    buttonLoading.classList.remove('hidden');

    const formData = new FormData(this);
    const descriptionContent = editor.getData();
    formData.set('description', descriptionContent);

    console.log('Description content:', descriptionContent);
    console.log('FormData description:', formData.get('description'));

    // Dispatch upload started event
    window.dispatchEvent(new CustomEvent('upload-started'));

    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = Math.round((e.loaded / e.total) * 100);
            window.dispatchEvent(new CustomEvent('upload-progress', {
                detail: { progress: percentComplete }
            }));
        }
    });

    xhr.addEventListener('load', function() {
        window.dispatchEvent(new CustomEvent('upload-complete'));
        console.log('XHR Status:', xhr.status);
        console.log('XHR Response:', xhr.responseText);

        if (xhr.status >= 200 && xhr.status < 300) {
            const response = JSON.parse(xhr.responseText || '{}');
            console.log('Parsed Response:', response);

            // Show success toast
            if (response.message) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', title: response.message }
                }));
            }

            // Redirect after a brief delay to allow toast to be seen
            setTimeout(() => {
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    window.location.href = "{{ route('staff.proposals.index') }}";
                }
            }, 500);
        } else {
            console.error('Upload failed with status:', xhr.status);
            console.error('Response:', xhr.responseText);
            // Handle errors
            submitButton.disabled = false;
            buttonText.classList.remove('hidden');
            buttonLoading.classList.add('hidden');
            if (xhr.status === 422) {
                const response = JSON.parse(xhr.responseText || '{}');
                const errors = response.errors || {};
                const errorMessages = Object.values(errors).flat().join(', ');
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'error', title: errorMessages || 'Validation error' }
                }));
            } else {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'error', title: 'An error occurred during upload.' }
                }));
            }
        }
    });

    xhr.addEventListener('error', function() {
        window.dispatchEvent(new CustomEvent('upload-error'));
        submitButton.disabled = false;
        buttonText.classList.remove('hidden');
        buttonLoading.classList.add('hidden');
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { type: 'error', title: 'Upload failed. Please try again.' }
        }));
    });

    const formAction = this.getAttribute('action');
    console.log('Form action URL:', formAction);

    xhr.open('POST', formAction);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
    xhr.send(formData);
});
</script>
@endpush
