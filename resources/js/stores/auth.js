import { defineStore } from 'pinia';
import api, { extractData, extractError } from '../api/client';
import { useNotificationsStore } from './notifications';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('auth_token'),
        initialized: false,
        loading: false,
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token && state.user),
        roles: (state) => state.user?.roles ?? [],
        isStudent: (state) => state.user?.roles?.includes('student') ?? false,
        isTrainer: (state) => state.user?.roles?.includes('trainer') ?? false,
        isAdmin: (state) => (state.user?.roles?.includes('admin') ?? false)
            || (state.user?.roles?.includes('gym_admin') ?? false),
        isPlatformAdmin: (state) => state.user?.roles?.includes('admin') ?? false,
        isGymAdmin: (state) => state.user?.roles?.includes('gym_admin') ?? false,
        managedGymIds: (state) => state.user?.managed_gym_ids ?? [],
        displayName: (state) => state.user?.name ?? 'Usuário',
    },

    actions: {
        setSession(token, user) {
            this.token = token;
            this.user = user;
            localStorage.setItem('auth_token', token);
        },

        clearSession() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('auth_token');
        },

        async initialize() {
            if (! this.token) {
                this.initialized = true;

                return;
            }

            try {
                await this.fetchMe();
                await useNotificationsStore().fetchUnreadCount();
            } catch {
                this.clearSession();
            } finally {
                this.initialized = true;
            }
        },

        async login(credentials) {
            this.loading = true;

            try {
                const response = await api.post('/auth/login', credentials);
                const payload = extractData(response);
                this.setSession(payload.token, payload.user);
                await useNotificationsStore().fetchUnreadCount();

                return payload;
            } catch (error) {
                throw extractError(error);
            } finally {
                this.loading = false;
            }
        },

        async register(data) {
            this.loading = true;

            try {
                const response = await api.post('/auth/register', data);
                const payload = extractData(response);

                if (payload.token) {
                    this.setSession(payload.token, payload.user);
                }

                return payload;
            } catch (error) {
                throw extractError(error);
            } finally {
                this.loading = false;
            }
        },

        async fetchMe() {
            const response = await api.get('/auth/me');
            this.user = extractData(response);

            return this.user;
        },

        async logout() {
            try {
                if (this.token) {
                    await api.post('/auth/logout');
                }
            } finally {
                this.clearSession();
                useNotificationsStore().reset();
            }
        },
    },
});
