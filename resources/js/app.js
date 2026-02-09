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

// Share to KingsChat component
Alpine.data('shareToKingsChat', () => ({
    sharing: false,

    async share(title, url, type) {
        // Check if KingsChat SDK is loaded
        if (!window.KingsChatSDK) {
            this.$dispatch('toast', {
                type: 'error',
                title: 'KingsChat not available',
                message: 'Please ensure KingsChat is installed and you are logged in.'
            });
            return;
        }

        this.sharing = true;

        try {
            // Format message based on type
            const emoji = this.getTypeEmoji(type);
            const message = `${emoji} ${title}\n\nView: ${url}`;

            // Call KingsChat SDK share method
            await window.KingsChatSDK.share({
                type: 'text',
                message: message,
                url: url
            });

            this.$dispatch('toast', {
                type: 'success',
                title: 'Shared successfully!',
                message: 'Message sent via KingsChat'
            });
        } catch (error) {
            console.error('KingsChat share error:', error);

            // Fallback: Copy to clipboard
            this.copyToClipboard(title, url, type);
        } finally {
            this.sharing = false;
        }
    },

    getTypeEmoji(type) {
        const emojis = {
            'report': '📊',
            'proposal': '📄',
            'comment': '💬',
            'announcement': '📢'
        };
        return emojis[type] || '📎';
    },

    copyToClipboard(title, url, type) {
        const emoji = this.getTypeEmoji(type);
        const message = `${emoji} ${title}\n\nView: ${url}`;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(message).then(() => {
                this.$dispatch('toast', {
                    type: 'info',
                    title: 'Copied to clipboard',
                    message: 'Paste this message in KingsChat manually'
                });
            }).catch(() => {
                this.$dispatch('toast', {
                    type: 'error',
                    title: 'Share failed',
                    message: 'Unable to share or copy message'
                });
            });
        } else {
            this.$dispatch('toast', {
                type: 'error',
                title: 'Share failed',
                message: 'Clipboard not available. Try using a modern browser.'
            });
        }
    }
}));

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine
Alpine.start();
