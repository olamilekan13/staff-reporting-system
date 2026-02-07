@props([
    'name',
    'label' => null,
    'accept' => null,
    'maxSize' => null,
    'multiple' => false,
    'error' => null,
])

<div x-data="{
    files: [],
    dragOver: false,

    handleFiles(fileList) {
        const newFiles = Array.from(fileList);

        @if($maxSize)
        const maxBytes = {{ $maxSize }};
        for (const file of newFiles) {
            if (file.size > maxBytes) {
                $dispatch('toast', {
                    type: 'error',
                    title: `File '${file.name}' exceeds the maximum size of ${this.formatSize(maxBytes)}.`
                });
                return;
            }
        }
        @endif

        @if($multiple)
            this.files = [...this.files, ...newFiles];
        @else
            this.files = newFiles.slice(0, 1);
        @endif
    },

    removeFile(index) {
        this.files.splice(index, 1);
    },

    formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
}">
    @if($label)
        <label class="label">
            {{ $label }}
        </label>
    @endif

    {{-- Drop zone --}}
    <div
        @dragover.prevent="dragOver = true"
        @dragleave.prevent="dragOver = false"
        @drop.prevent="dragOver = false; handleFiles($event.dataTransfer.files)"
        :class="dragOver ? 'border-primary-400 bg-primary-50' : 'border-gray-300'"
        class="border-2 border-dashed rounded-lg p-6 text-center transition-colors duration-200 cursor-pointer hover:border-primary-400"
        @click="$refs.fileInput.click()"
    >
        <input
            type="file"
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            x-ref="fileInput"
            @change="handleFiles($event.target.files)"
            @if($accept) accept="{{ $accept }}" @endif
            {{ $multiple ? 'multiple' : '' }}
            class="hidden"
        >

        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        <p class="text-sm text-gray-600">
            <span class="font-medium text-primary-600">Click to upload</span> or drag and drop
        </p>
        @if($accept)
            <p class="text-xs text-gray-400 mt-1">{{ $accept }}</p>
        @endif
        @if($maxSize)
            <p class="text-xs text-gray-400 mt-0.5">Max size: <span x-text="formatSize({{ $maxSize }})"></span></p>
        @endif
    </div>

    {{-- File list --}}
    <template x-if="files.length > 0">
        <ul class="mt-3 space-y-2">
            <template x-for="(file, index) in files" :key="index">
                <li class="flex items-center gap-3 text-sm bg-gray-50 rounded-lg px-3 py-2">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <span class="flex-1 truncate" x-text="file.name"></span>
                    <span class="text-gray-400 text-xs shrink-0" x-text="formatSize(file.size)"></span>
                    <button type="button" @click="removeFile(index)" class="text-gray-400 hover:text-red-500 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </li>
            </template>
        </ul>
    </template>

    @if($error)
        <p class="mt-1.5 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>
