{{-- KingsChat Share Modal --}}
<div x-data="shareToKingsChat"
     @kingschat-share.window="share($event.detail.title, $event.detail.url, $event.detail.type)">

    <x-modal name="kingschat-share" maxWidth="md">
        <x-slot:title>
            Share to KingsChat
        </x-slot:title>

        {{-- Success State --}}
        <div x-show="sent" x-cloak class="text-center py-6">
            <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <p class="text-sm font-medium text-gray-900">Message sent!</p>
            <p class="text-sm text-gray-500 mt-1" x-text="'Shared with ' + (selectedUser ? selectedUser.full_name : '')"></p>
        </div>

        {{-- Search + Select State --}}
        <div x-show="!sent">
            {{-- Error banner --}}
            <div x-show="error" x-cloak
                 class="mb-3 p-3 bg-red-50 text-red-700 text-sm rounded-lg flex items-start gap-2">
                <svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span x-text="error"></span>
            </div>

            {{-- Selected user display --}}
            <div x-show="selectedUser" x-cloak class="mb-4 flex items-center gap-3 p-3 bg-primary-50 border border-primary-100 rounded-lg">
                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center overflow-hidden shrink-0">
                    <template x-if="selectedUser && selectedUser.profile_photo_url">
                        <img :src="selectedUser.profile_photo_url" class="w-10 h-10 rounded-full object-cover" />
                    </template>
                    <template x-if="selectedUser && !selectedUser.profile_photo_url">
                        <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </template>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate" x-text="selectedUser ? selectedUser.full_name : ''"></p>
                    <p class="text-xs text-gray-500 truncate" x-text="selectedUser ? '@' + selectedUser.kingschat_id : ''"></p>
                </div>
                <button @click="deselectUser()" type="button"
                        class="text-gray-400 hover:text-gray-600 p-1 rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Search input (shown when no user selected) --}}
            <div x-show="!selectedUser">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search recipient</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input type="text"
                           x-model="searchQuery"
                           @input="onSearchInput()"
                           placeholder="Type a name or KingsChat ID..."
                           class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm" />
                </div>
            </div>

            {{-- Loading indicator --}}
            <div x-show="searching" x-cloak class="flex items-center justify-center py-4">
                <svg class="animate-spin h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2 text-sm text-gray-500">Searching...</span>
            </div>

            {{-- Results list --}}
            <div x-show="!selectedUser && searchResults.length > 0 && !searching" x-cloak
                 class="mt-2 max-h-60 overflow-y-auto divide-y divide-gray-100 border border-gray-200 rounded-lg">
                <template x-for="user in searchResults" :key="user.id">
                    <button @click="selectUser(user)" type="button"
                            class="w-full flex items-center gap-3 p-3 hover:bg-gray-50 transition-colors text-left">
                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden shrink-0">
                            <template x-if="user.profile_photo_url">
                                <img :src="user.profile_photo_url" class="w-8 h-8 rounded-full object-cover" />
                            </template>
                            <template x-if="!user.profile_photo_url">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate" x-text="user.full_name"></p>
                            <p class="text-xs text-gray-500" x-text="user.department || 'No department'"></p>
                        </div>
                        <span class="text-xs text-gray-400 truncate max-w-[120px]" x-text="'@' + user.kingschat_id"></span>
                    </button>
                </template>
            </div>

            {{-- No results --}}
            <div x-show="!selectedUser && searchQuery.length >= 2 && searchResults.length === 0 && !searching" x-cloak
                 class="text-center py-4 text-sm text-gray-500">
                No users found matching your search.
            </div>

            {{-- Hint text --}}
            <div x-show="!selectedUser && searchQuery.length < 2 && !searching" x-cloak
                 class="text-center py-4 text-sm text-gray-400">
                Type at least 2 characters to search
            </div>

            {{-- Message preview --}}
            <div x-show="selectedUser" x-cloak class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Message preview</label>
                <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700 whitespace-pre-line border border-gray-200"
                     x-text="getMessageText()">
                </div>
            </div>
        </div>

        <x-slot:footer>
            <button @click="$dispatch('close-modal', 'kingschat-share')"
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <span x-text="sent ? 'Close' : 'Cancel'"></span>
            </button>
            <button x-show="!sent"
                    @click="send()"
                    :disabled="!selectedUser || sending"
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!sending">Send via KingsChat</span>
                <span x-show="sending" x-cloak class="inline-flex items-center gap-1.5">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sending...
                </span>
            </button>
        </x-slot:footer>
    </x-modal>
</div>
