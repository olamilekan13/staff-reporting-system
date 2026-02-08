@extends('layouts.app')

@section('title', 'Create Report')
@section('page-title', 'Create Report')

@section('content')
    <div class="max-w-3xl">
        {{-- Back link --}}
        <a href="{{ route('staff.reports.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Reports
        </a>

        <x-card title="New Report">
            <form method="POST" action="{{ route('staff.reports.store') }}" enctype="multipart/form-data" id="report-form">
                @csrf

                <div class="space-y-5">
                    <x-input name="title" label="Title" :value="old('title')" :error="$errors->first('title')" required placeholder="Enter report title" />

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
                    <x-button type="submit" name="action" value="draft" variant="secondary" class="submit-btn">
                        <span class="button-text">Save as Draft</span>
                        <span class="button-loading hidden">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </x-button>
                    <x-button type="submit" name="action" value="submit" variant="primary" class="submit-btn">
                        <span class="button-text">Submit Report</span>
                        <span class="button-loading hidden">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submitting...
                        </span>
                    </x-button>
                    <a href="{{ route('staff.reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
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

    // Handle form submission with upload progress
    let clickedButton = null;
    document.querySelectorAll('.submit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            clickedButton = this;
        });
    });

    document.getElementById('report-form').addEventListener('submit', function(e) {
        const fileInput = this.querySelector('input[type="file"]');
        const buttons = this.querySelectorAll('.submit-btn');

        // If no file is selected, just submit normally without AJAX
        if (!fileInput || !fileInput.files.length) {
            console.log('No file selected, submitting form normally');
            // Show loading state on clicked button
            if (clickedButton) {
                const buttonText = clickedButton.querySelector('.button-text');
                const buttonLoading = clickedButton.querySelector('.button-loading');
                buttonText.classList.add('hidden');
                buttonLoading.classList.remove('hidden');
            }
            // Disable all buttons
            buttons.forEach(btn => btn.disabled = true);
            return; // Let form submit normally
        }

        console.log('File selected, using AJAX upload');
        e.preventDefault();

        // Show loading state on clicked button
        if (clickedButton) {
            const buttonText = clickedButton.querySelector('.button-text');
            const buttonLoading = clickedButton.querySelector('.button-loading');
            buttonText.classList.add('hidden');
            buttonLoading.classList.remove('hidden');
        }

        // Disable all buttons
        buttons.forEach(btn => btn.disabled = true);

        const formData = new FormData(this);
        const actionValue = clickedButton ? clickedButton.value : 'submit';

        if (actionValue) {
            formData.set('action', actionValue);
        }

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
                        window.location.href = "{{ route('staff.reports.index') }}";
                    }
                }, 500);
            } else {
                console.error('Upload failed with status:', xhr.status);
                console.error('Response:', xhr.responseText);
                buttons.forEach(btn => {
                    btn.disabled = false;
                    const buttonText = btn.querySelector('.button-text');
                    const buttonLoading = btn.querySelector('.button-loading');
                    buttonText.classList.remove('hidden');
                    buttonLoading.classList.add('hidden');
                });
                if (xhr.status === 422) {
                    console.error('Validation errors:', xhr.responseText);
                    alert('Validation error: ' + xhr.responseText);
                } else {
                    alert('An error occurred during upload. Status: ' + xhr.status);
                }
            }
        });

        xhr.addEventListener('error', function() {
            console.error('XHR Error event triggered');
            window.dispatchEvent(new CustomEvent('upload-error'));
            buttons.forEach(btn => {
                btn.disabled = false;
                const buttonText = btn.querySelector('.button-text');
                const buttonLoading = btn.querySelector('.button-loading');
                buttonText.classList.remove('hidden');
                buttonLoading.classList.add('hidden');
            });
            alert('Upload failed. Please try again. Check console for details.');
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
