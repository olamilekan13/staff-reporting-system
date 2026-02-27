@extends('layouts.app')

@section('title', 'Edit Announcement')
@section('page-title', 'Edit Announcement')

@section('content')
        {{-- Back link --}}
        <a href="{{ route('admin.announcements.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Announcements
        </a>

        <x-card title="Edit Announcement">
            <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}"
                  enctype="multipart/form-data"
                  x-data="announcementForm()" id="announcement-form">
                @csrf
                @method('PUT')

                <div class="space-y-5">
                    {{-- Announcement Type --}}
                    <div>
                        <label for="announcement_type" class="label">
                            Announcement Type <span class="text-red-500">*</span>
                        </label>
                        <select name="announcement_type" id="announcement_type" x-model="announcementType" required
                            class="input @error('announcement_type') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                            <option value="text">Text / General</option>
                            <option value="video_upload">Upload Video File</option>
                            <option value="audio_upload">Upload Audio File</option>
                            <option value="youtube">YouTube Video</option>
                            <option value="vimeo">Vimeo Video</option>
                            <option value="livestream">Live Stream</option>
                        </select>
                        @error('announcement_type')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-input name="title" label="Title" :value="old('title', $announcement->title)" :error="$errors->first('title')" required placeholder="Enter announcement title" />

                    {{-- Video / Audio file upload --}}
                    <div x-show="announcementType === 'video_upload' || announcementType === 'audio_upload'" x-transition>
                        {{-- Current media preview --}}
                        @if($announcement->hasMedia('announcement_media') && in_array($announcement->announcement_type, ['video_upload', 'audio_upload']))
                            <div class="mb-3">
                                <p class="text-sm font-medium text-gray-700 mb-2">Current Media</p>
                                <x-media-player :announcement="$announcement" class="mb-2" />
                                <p class="text-xs text-gray-500">Upload a new file below to replace it, or leave blank to keep the current one.</p>
                            </div>
                        @endif

                        <label class="label">
                            <span x-text="announcementType === 'audio_upload' ? 'Audio File' : 'Video File'"></span>
                            @if(!$announcement->hasMedia('announcement_media'))
                                <span class="text-red-500">*</span>
                            @else
                                <span class="text-xs text-gray-400">(optional — replace only)</span>
                            @endif
                        </label>
                        <input type="file" name="media_file"
                            :accept="announcementType === 'audio_upload' ? 'audio/mpeg,audio/wav,audio/ogg,audio/mp3' : 'video/mp4,video/webm,video/quicktime'"
                            class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        <p class="text-xs text-gray-500 mt-1">Max 500 MB. For longer videos, use YouTube or Vimeo instead.</p>
                        @error('media_file')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- YouTube URL --}}
                    <div x-show="announcementType === 'youtube'" x-transition>
                        <label class="label">YouTube URL <span class="text-red-500">*</span></label>
                        <input type="url" name="media_url"
                            placeholder="https://www.youtube.com/watch?v=..."
                            class="input @error('media_url') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                            value="{{ old('media_url', $announcement->media_url) }}">
                        @error('media_url')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Vimeo URL --}}
                    <div x-show="announcementType === 'vimeo'" x-transition>
                        <label class="label">Vimeo URL <span class="text-red-500">*</span></label>
                        <input type="url" name="media_url"
                            placeholder="https://vimeo.com/123456789"
                            class="input @error('media_url') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                            value="{{ old('media_url', $announcement->media_url) }}">
                        @error('media_url')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Livestream info --}}
                    <div x-show="announcementType === 'livestream'" x-transition>
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <p class="text-sm font-medium text-blue-800">Live Stream</p>
                            <p class="text-sm text-blue-600 mt-1">
                                When staff open this announcement, they'll see the live stream player embedded.
                                Configure your stream URL or embed code in <strong>Settings &rarr; Live Stream</strong>.
                            </p>
                            <div class="mt-3"
                                 x-data="{ status: 'checking' }"
                                 x-init="fetch('{{ route('stream.status') }}').then(r => r.json()).then(d => status = d.is_live ? 'live' : 'offline').catch(() => status = 'error')">
                                <span x-show="status === 'live'" class="text-green-600 text-sm font-semibold">&#9679; Stream is currently LIVE</span>
                                <span x-show="status === 'offline'" class="text-gray-500 text-sm">&#9675; Stream is currently offline</span>
                                <span x-show="status === 'checking'" class="text-gray-400 text-sm">Checking stream status&hellip;</span>
                                <span x-show="status === 'error'" class="text-red-500 text-sm">Could not reach stream server</span>
                            </div>
                        </div>
                    </div>

                    {{-- Media title (optional, for embedded / livestream types) --}}
                    <div x-show="['youtube','vimeo','livestream'].includes(announcementType)" x-transition>
                        <label class="label">Media Title <span class="text-xs text-gray-400">(optional)</span></label>
                        <input type="text" name="media_title"
                            placeholder="Leave blank to use the announcement title"
                            class="input"
                            value="{{ old('media_title', $announcement->media_title) }}">
                        @error('media_title')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Content (CKEditor) --}}
                    <div>
                        <label for="content" class="label">
                            <span x-text="announcementType === 'text' ? 'Content *' : 'Additional Notes (optional)'">Content</span>
                        </label>
                        <textarea name="content" id="content">{{ old('content', $announcement->content) }}</textarea>
                        @error('content')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-select name="priority" label="Priority" :selected="old('priority', $announcement->priority)" :error="$errors->first('priority')" required
                        :options="['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent']" />

                    {{-- Target Type --}}
                    <div>
                        <label for="target_type" class="label">
                            Target Audience <span class="text-red-500">*</span>
                        </label>
                        <select name="target_type" id="target_type" x-model="targetType" required
                            class="input @error('target_type') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                            <option value="all" {{ old('target_type', $announcement->target_type) === 'all' ? 'selected' : '' }}>All Users</option>
                            <option value="departments" {{ old('target_type', $announcement->target_type) === 'departments' ? 'selected' : '' }}>Specific Departments</option>
                            <option value="users" {{ old('target_type', $announcement->target_type) === 'users' ? 'selected' : '' }}>Specific Users</option>
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
                                        {{ in_array($department->id, old('department_ids', $announcement->departments->pluck('id')->toArray())) ? 'checked' : '' }}>
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
                                        {{ in_array($userItem->id, old('user_ids', $announcement->users->pluck('id')->toArray())) ? 'checked' : '' }}>
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
                                {{ old('is_pinned', $announcement->is_pinned) ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Pin this announcement</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-6">Pinned announcements appear at the top of the list</p>
                    </div>

                    {{-- Schedule --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input name="starts_at" label="Starts At" type="datetime-local"
                            :value="old('starts_at', $announcement->starts_at?->format('Y-m-d\TH:i'))"
                            :error="$errors->first('starts_at')" />
                        <x-input name="expires_at" label="Expires At" type="datetime-local"
                            :value="old('expires_at', $announcement->expires_at?->format('Y-m-d\TH:i'))"
                            :error="$errors->first('expires_at')" />
                    </div>
                    <p class="text-xs text-gray-500 -mt-3">Leave empty to publish immediately with no expiration</p>
                </div>

                <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                    <x-button type="submit" variant="primary">Update Announcement</x-button>
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
            targetType: '{{ old('target_type', $announcement->target_type) }}',
            announcementType: '{{ old('announcement_type', $announcement->announcement_type) }}',
            mediaUrl: '{{ old('media_url', $announcement->media_url) }}',
            userSearch: '',
        }
    }

    ClassicEditor.create(document.querySelector('#content'), {
        toolbar: ['heading', '|', 'bold', 'italic', 'underline', '|', 'bulletedList', 'numberedList', '|', 'link', 'blockQuote', '|', 'undo', 'redo'],
        placeholder: 'Enter announcement content...',
    }).catch(error => console.error(error));
</script>
@endpush
