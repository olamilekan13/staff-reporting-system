import './bootstrap';

// Alpine.js
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';

// Register Alpine plugins
Alpine.plugin(focus);
Alpine.plugin(collapse);

// Layout component - controls mobile sidebar toggle
Alpine.data('appLayout', () => ({
    sidebarOpen: false,
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
    },
    closeSidebar() {
        this.sidebarOpen = false;
    },
}));

// Toast notification component
Alpine.data('toastNotification', () => ({
    toasts: [],
    nextId: 0,

    addToast({ type = 'info', title = '', message = '', duration = 5000 }) {
        const id = this.nextId++;
        this.toasts.push({ id, type, title, message, visible: true });

        if (duration > 0) {
            setTimeout(() => this.removeToast(id), duration);
        }
    },

    removeToast(id) {
        const toast = this.toasts.find(t => t.id === id);
        if (toast) {
            toast.visible = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 300);
        }
    },

    getIcon(type) {
        const icons = {
            success: `<svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`,
            error: `<svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>`,
            warning: `<svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>`,
            info: `<svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>`,
        };
        return icons[type] || icons.info;
    },
}));

// KingsChat SDK service
import { sendKingsChatMessage } from './kingschat-service';

// Share to KingsChat component
Alpine.data('shareToKingsChat', () => ({
    // Share context
    shareTitle: '',
    shareUrl: '',
    shareType: '',

    // Modal state
    searchQuery: '',
    searchResults: [],
    searching: false,
    selectedUser: null,

    // Send state
    sending: false,
    sent: false,
    error: '',

    // Debounce timer
    _searchTimeout: null,

    share(title, url, type) {
        this.shareTitle = title;
        this.shareUrl = url;
        this.shareType = type;
        this.resetState();
        this.$dispatch('open-modal', 'kingschat-share');
    },

    resetState() {
        this.searchQuery = '';
        this.searchResults = [];
        this.searching = false;
        this.selectedUser = null;
        this.sending = false;
        this.sent = false;
        this.error = '';
    },

    onSearchInput() {
        clearTimeout(this._searchTimeout);
        this.error = '';

        if (this.searchQuery.length < 2) {
            this.searchResults = [];
            this.searching = false;
            return;
        }

        this.searching = true;
        this._searchTimeout = setTimeout(() => this.searchUsers(), 300);
    },

    async searchUsers() {
        try {
            const response = await window.axios.get('/users/search', {
                params: { q: this.searchQuery }
            });
            this.searchResults = response.data.users;
        } catch {
            this.searchResults = [];
            this.error = 'Failed to search users. Please try again.';
        } finally {
            this.searching = false;
        }
    },

    selectUser(user) {
        this.selectedUser = user;
        this.error = '';
    },

    deselectUser() {
        this.selectedUser = null;
    },

    getMessageText() {
        const emojis = {
            'report': '📊', 'proposal': '📄',
            'comment': '💬', 'announcement': '📢'
        };
        const emoji = emojis[this.shareType] || '📎';
        return `${emoji} ${this.shareTitle}\n\nView: ${this.shareUrl}`;
    },

    async send() {
        if (!this.selectedUser) return;

        this.sending = true;
        this.error = '';

        try {
            await sendKingsChatMessage(
                this.selectedUser.kingschat_id,
                this.getMessageText()
            );
            this.sent = true;
            this.$dispatch('toast', {
                type: 'success',
                title: 'Message sent!',
                message: `Shared with ${this.selectedUser.full_name} on KingsChat`
            });
            setTimeout(() => {
                this.$dispatch('close-modal', 'kingschat-share');
            }, 1500);
        } catch (err) {
            console.error('[KingsChat]', err.message, err.status || '');
            if (err.message === 'User closed window before allowing access') {
                this.error = 'KingsChat login was cancelled. Please try again.';
            } else if (err.message === 'You have to enable popups to show login window') {
                this.error = 'Please enable popups in your browser to connect with KingsChat.';
            } else if (err.status === 500) {
                this.error = 'KingsChat server error. The message has been copied to your clipboard instead.';
                this.copyToClipboard();
            } else if (err.status === 404) {
                this.error = `User "${this.selectedUser.kingschat_id}" was not found on KingsChat.`;
            } else if (err.status >= 400) {
                this.error = `KingsChat error: ${err.message}`;
            } else {
                this.error = `Failed to send: ${err.message}`;
            }
        } finally {
            this.sending = false;
        }
    },

    copyToClipboard() {
        const message = this.getMessageText();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(message).catch(() => {});
        }
    },
}));

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine
Alpine.start();
