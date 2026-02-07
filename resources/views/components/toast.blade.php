<div
    x-data="toastNotification"
    @toast.window="addToast($event.detail)"
    class="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm w-full pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            class="pointer-events-auto bg-white rounded-lg shadow-lg border border-gray-200 p-4 flex items-start gap-3"
        >
            {{-- Icon --}}
            <div class="shrink-0" x-html="getIcon(toast.type)"></div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900" x-text="toast.title"></p>
                <p class="text-sm text-gray-500 mt-0.5" x-text="toast.message" x-show="toast.message"></p>
            </div>

            {{-- Close button --}}
            <button @click="removeToast(toast.id)" class="shrink-0 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
