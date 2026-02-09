@extends('layouts.app')

@section('title', 'Create Announcement')
@section('page-title', 'Create Announcement')

@section('content')
        {{-- Back link --}}
        <a href="{{ route('admin.announcements.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Announcements
        </a>

        <x-card title="New Announcement">
            <form method="POST" action="{{ route('admin.announcements.store') }}" x-data="announcementForm()" id="announcement-form">
                @csrf

                <div class="space-y-5">
                    <x-input name="title" label="Title" :value="old('title')" :error="$errors->first('title')" required placeholder="Enter announcement title" />

                    {{-- Content (CKEditor) --}}
                    <div>
                        <label for="content" class="label">
                            Content <span class="text-red-500">*</span>
                        </label>
                        <textarea name="content" id="content">{{ old('content') }}</textarea>
                        @error('content')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-select name="priority" label="Priority" :selected="old('priority', 'medium')" :error="$errors->first('priority')" required
                        :options="['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent']" />

                    {{-- Target Type --}}
                    <div>
                        <label for="target_type" class="label">
                            Target Audience <span class="text-red-500">*</span>
                        </label>
                        <select name="target_type" id="target_type" x-model="targetType" required
                            class="input @error('target_type') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                            <option value="all" {{ old('target_type', 'all') === 'all' ? 'selected' : '' }}>All Users</option>
                            <option value="departments" {{ old('target_type') === 'departments' ? 'selected' : '' }}>Specific Departments</option>
                            <option value="users" {{ old('target_type') === 'users' ? 'selected' : '' }}>Specific Users</option>
                        </select>
                        @error('target_type')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Department multi-select --}}
                    <div x-show="targetType === 'departments'" x-transition>
                        <label class="label">Select Departments <span class="text-red-500">*</span></label>
                        <div class="border border-gray-300 rounded-lg max-h-48 overflow-y-auto p-3 space-y-2">
                            @foreach($departments as $department)
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:text-gray-900">
                                    <input type="checkbox" name="department_ids[]" value="{{ $department->id }}"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                        {{ in_array($department->id, old('department_ids', [])) ? 'checked' : '' }}>
                                    {{ $department->name }}
                                </label>
                            @endforeach
                        </div>
                        @error('department_ids')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- User multi-select --}}
                    <div x-show="targetType === 'users'" x-transition>
                        <label class="label">Select Users <span class="text-red-500">*</span></label>
                        <input type="text" x-model="userSearch" placeholder="Search users..."
                            class="input mb-2">
                        <div class="border border-gray-300 rounded-lg max-h-48 overflow-y-auto p-3 space-y-2">
                            @foreach($users as $userItem)
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:text-gray-900"
                                    x-show="!userSearch || '{{ strtolower($userItem->first_name . ' ' . $userItem->last_name) }}'.includes(userSearch.toLowerCase())"
                                    x-transition>
                                    <input type="checkbox" name="user_ids[]" value="{{ $userItem->id }}"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                        {{ in_array($userItem->id, old('user_ids', [])) ? 'checked' : '' }}>
                                    {{ $userItem->first_name }} {{ $userItem->last_name }}
                                </label>
                            @endforeach
                        </div>
                        @error('user_ids')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Is Pinned --}}
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_pinned" value="0">
                            <input type="checkbox" name="is_pinned" value="1"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                {{ old('is_pinned') ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Pin this announcement</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-6">Pinned announcements appear at the top of the list</p>
                    </div>

                    {{-- Schedule --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input name="starts_at" label="Starts At" type="datetime-local" :value="old('starts_at')" :error="$errors->first('starts_at')" />
                        <x-input name="expires_at" label="Expires At" type="datetime-local" :value="old('expires_at')" :error="$errors->first('expires_at')" />
                    </div>
                    <p class="text-xs text-gray-500 -mt-3">Leave empty to publish immediately with no expiration</p>
                </div>

                <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                    <x-button type="submit" variant="primary">Create Announcement</x-button>
                    <a href="{{ route('admin.announcements.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
                </div>
            </form>
        </x-card>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    function announcementForm() {
        return {
            targetType: '{{ old('target_type', 'all') }}',
            userSearch: '',
        }
    }

    ClassicEditor.create(document.querySelector('#content'), {
        toolbar: ['heading', '|', 'bold', 'italic', 'underline', '|', 'bulletedList', 'numberedList', '|', 'link', 'blockQuote', '|', 'undo', 'redo'],
        placeholder: 'Enter announcement content...',
    }).catch(error => console.error(error));
</script>
@endpush
