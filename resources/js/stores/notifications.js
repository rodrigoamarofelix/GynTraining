import { defineStore } from 'pinia';
import api, { extractData } from '../api/client';

const POLL_INTERVAL_MS = 15000;

export const useNotificationsStore = defineStore('notifications', {
    state: () => ({
        unreadCount: 0,
        loading: false,
        pollTimer: null,
    }),

    getters: {
        hasUnread: (state) => state.unreadCount > 0,
        badgeLabel: (state) => (state.unreadCount > 99 ? '99+' : String(state.unreadCount)),
    },

    actions: {
        async fetchUnreadCount() {
            this.loading = true;

            try {
                const response = await api.get('/notifications/unread-count');
                this.unreadCount = extractData(response)?.unread_count ?? 0;
            } catch {
                this.unreadCount = 0;
            } finally {
                this.loading = false;
            }
        },

        setUnreadCount(count) {
            this.unreadCount = Math.max(0, count);
        },

        decrementUnread() {
            if (this.unreadCount > 0) {
                this.unreadCount -= 1;
            }
        },

        startPolling() {
            this.stopPolling();
            this.pollTimer = setInterval(() => {
                this.fetchUnreadCount();
            }, POLL_INTERVAL_MS);
        },

        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        reset() {
            this.stopPolling();
            this.unreadCount = 0;
            this.loading = false;
        },
    },
});
