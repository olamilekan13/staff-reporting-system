@extends('layouts.app')

@section('title', 'Edit Video')
@section('page-title', 'Edit Video')

@section('content')
    <a href="{{ route('admin.videos.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to Videos
    </a>

    <x-card title="Edit Video">
        <form method="POST" action="{{ route('admin.videos.update', $video) }}"
              enctype="multipart/form-data"
              x-data="videoForm()">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <x-input name="title" label="Title" :value="old('title', $video->title)" :error="$errors->first('title')" required placeholder="Enter video title" />

                {{-- Source Type --}}
                <div>
                    <label for="source_type" class="label">Video Source <span class="text-red-500">*</span></label>
                    <select name="source_type" id="source_type" x-model="sourceType" required
                        class="input @error('source_type') border-red-300 @enderror">
                        <option value="upload">Upload Video File</option>
                        <option value="youtube">YouTube URL</option>
                        <option value="vimeo">Vimeo URL</option>
                        <option value="m3u8">HLS Stream (M3U8)</option>
                        <option value="embed">Embed Code</option>
                    </select>
                    @error('source_type')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- File upload --}}
                <div x-show="sourceType === 'upload'" x-transition>
                    <label class="label">Video File</label>
                    @if($video->source_type === 'upload' && $video->getFirstMedia('video_file'))
                        <p class="text-sm text-gray-500 mb-2">Current: {{ $video->getFirstMedia('video_file')->file_name }}</p>
                    @endif
                    <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime"
                        class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep current file. Max 500 MB.</p>
                    @error('video_file')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- YouTube URL --}}
                <div x-show="sourceType === 'youtube'" x-transition>
                    <label class="label">YouTube URL <span class="text-red-500">*</span></label>
                    <input type="url" name="source_url" placeholder="https://www.youtube.com/watch?v=..."
                        class="input @error('source_url') border-red-300 @enderror"
                        value="{{ old('source_url', $video->source_type === 'youtube' ? $video->source_url : '') }}">
                    @error('source_url')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Vimeo URL --}}
                <div x-show="sourceType === 'vimeo'" x-transition>
                    <label class="label">Vimeo URL <span class="text-red-500">*</span></label>
                    <input type="url" name="source_url" placeholder="https://vimeo.com/123456789"
                        class="input @error('source_url') border-red-300 @enderror"
                        value="{{ old('source_url', $video->source_type === 'vimeo' ? $video->source_url : '') }}">
                    @error('source_url')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- M3U8 URL --}}
                <div x-show="sourceType === 'm3u8'" x-transition>
                    <label class="label">HLS Stream URL <span class="text-red-500">*</span></label>
                    <input type="url" name="source_url" placeholder="https://example.com/stream/video.m3u8"
                        class="input @error('source_url') border-red-300 @enderror"
                        value="{{ old('source_url', $video->source_type === 'm3u8' ? $video->source_url : '') }}">
                    @error('source_url')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Embed Code --}}
                <div x-show="sourceType === 'embed'" x-transition>
                    <label class="label">Embed Code (iframe) <span class="text-red-500">*</span></label>
                    <textarea name="embed_code" rows="3" placeholder='<iframe src="..." ...></iframe>'
                        class="input @error('embed_code') border-red-300 @enderror">{{ old('embed_code', $video->source_type === 'embed' ? $video->source_url : '') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Only &lt;iframe&gt; tags are allowed for security.</p>
                    @error('embed_code')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Thumbnail --}}
                <div>
                    <label class="label">Thumbnail <span class="text-xs text-gray-400">(optional)</span></label>
                    @if($video->getThumbnailUrl())
                        <img src="{{ $video->getThumbnailUrl() }}" alt="Current thumbnail" class="w-24 h-16 rounded object-cover mb-2">
                    @endif
                    <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    @error('thumbnail')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="label">Description <span class="text-xs text-gray-400">(optional)</span></label>
                    <textarea name="description" id="description" rows="3" placeholder="Video description..."
                        class="input @error('description') border-red-300 @enderror">{{ old('description', $video->description) }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-select name="video_category_id" label="Category" placeholder="No Category"
                    :selected="old('video_category_id', $video->video_category_id)"
                    :options="$categories->pluck('name', 'id')->toArray()" />

                <x-input name="duration_seconds" label="Duration (seconds)" type="number"
                    :value="old('duration_seconds', $video->duration_seconds)"
                    :error="$errors->first('duration_seconds')" placeholder="e.g. 3600 for 1 hour" />

                <x-select name="status" label="Status" :selected="old('status', $video->status)" :error="$errors->first('status')" required
                    :options="['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']" />

                <x-input name="publish_at" label="Schedule Publish Date" type="datetime-local"
                    :value="old('publish_at', $video->publish_at?->format('Y-m-d\TH:i'))"
                    :error="$errors->first('publish_at')" />

                {{-- Target Type --}}
                <div>
                    <label for="target_type" class="label">Target Audience <span class="text-red-500">*</span></label>
                    <select name="target_type" id="target_type" x-model="targetType" required
                        class="input @error('target_type') border-red-300 @enderror">
                        <option value="all">All Users</option>
                        <option value="departments">Specific Departments</option>
                        <option value="users">Specific Users</option>
                    </select>
                    @error('target_type')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Department multi-select --}}
                <div x-show="targetType === 'departments'" x-transition>
                    <label class="label">Select Departments <span class="text-red-500">*</span></label>
                    @php $selectedDepts = old('department_ids', $video->departments->pluck('id')->toArray()); @endphp
                    <div class="border border-gray-300 rounded-lg max-h-48 overflow-y-auto p-3 space-y-2">
                        @foreach($departments as $department)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:text-gray-900">
                                <input type="checkbox" name="department_ids[]" value="{{ $department->id }}"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    {{ in_array($department->id, $selectedDepts) ? 'checked' : '' }}>
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
                    <input type="text" x-model="userSearch" placeholder="Search users..." class="input mb-2">
                    @php $selectedUsers = old('user_ids', $video->users->pluck('id')->toArray()); @endphp
                    <div class="border border-gray-300 rounded-lg max-h-48 overflow-y-auto p-3 space-y-2">
                        @foreach($users as $userItem)
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer hover:text-gray-900"
                                x-show="!userSearch || '{{ strtolower($userItem->first_name . ' ' . $userItem->last_name) }}'.includes(userSearch.toLowerCase())"
                                x-transition>
                                <input type="checkbox" name="user_ids[]" value="{{ $userItem->id }}"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    {{ in_array($userItem->id, $selectedUsers) ? 'checked' : '' }}>
                                {{ $userItem->first_name }} {{ $userItem->last_name }}
                            </label>
                        @endforeach
                    </div>
                    @error('user_ids')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                <x-button type="submit" variant="primary">Update Video</x-button>
                <a href="{{ route('admin.videos.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
            </div>
        </form>
    </x-card>
@endsection

@push('scripts')
<script>
    function videoForm() {
        return {
            sourceType: '{{ old('source_type', $video->source_type) }}',
            targetType: '{{ old('target_type', $video->target_type) }}',
            userSearch: '',
        }
    }
</script>
@endpush
