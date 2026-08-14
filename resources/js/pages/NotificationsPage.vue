<template>
    <AppLayout subtitle="Notificações">
        <div class="mb-4 flex flex-wrap gap-3">
            <UiButton variant="secondary" :loading="markingAll" @click="markAllRead">
                Marcar todas como lidas
            </UiButton>
            <UiBadge variant="info">{{ notificationsStore.unreadCount }} não lidas</UiBadge>
        </div>

        <div v-if="loading" class="py-20 text-center text-slate-400">Carregando...</div>
        <UiAlert v-else-if="error" :message="error" />

        <div v-else class="space-y-3">
            <UiCard v-if="! notifications.length" title="Sem notificações">
                <p class="text-sm text-slate-400">Você está em dia.</p>
            </UiCard>

            <UiCard
                v-for="notification in notifications"
                :key="notification.id"
                :title="notification.title"
                :subtitle="formatDateTime(notification.created_at)"
            >
                <p class="text-sm text-slate-300">{{ notification.message }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <UiBadge :variant="notification.read_at ? 'default' : 'success'">
                        {{ notification.read_at ? 'Lida' : 'Nova' }}
                    </UiBadge>
                    <RouterLink
                        v-if="notificationAction(notification)"
                        :to="notificationAction(notification)"
                    >
                        <UiButton variant="secondary" size="sm">
                            {{ notificationActionLabel(notification) }}
                        </UiButton>
                    </RouterLink>
                    <UiButton
                        v-if="! notification.read_at"
                        variant="ghost"
                        size="sm"
                        @click="markRead(notification.id)"
                    >
                        Marcar como lida
                    </UiButton>
                </div>
            </UiCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api, { extractData, extractError } from '../api/client';
import UiAlert from '../components/ui/UiAlert.vue';
import UiBadge from '../components/ui/UiBadge.vue';
import UiButton from '../components/ui/UiButton.vue';
import UiCard from '../components/ui/UiCard.vue';
import AppLayout from '../layouts/AppLayout.vue';
import { useNotificationsStore } from '../stores/notifications';
import { formatDateTime } from '../utils/format';

const notificationsStore = useNotificationsStore();
const notifications = ref([]);
const loading = ref(true);
const markingAll = ref(false);
const error = ref('');

async function loadNotifications() {
    loading.value = true;

    try {
        const [listResponse, countResponse] = await Promise.all([
            api.get('/notifications', { params: { per_page: 20 } }),
            api.get('/notifications/unread-count'),
        ]);

        notifications.value = extractData(listResponse);
        notificationsStore.setUnreadCount(extractData(countResponse)?.unread_count ?? 0);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loading.value = false;
    }
}

async function markRead(id) {
    await api.post(`/notifications/${id}/read`);
    await loadNotifications();
}

async function markAllRead() {
    markingAll.value = true;

    try {
        await api.post('/notifications/read-all');
        await loadNotifications();
    } finally {
        markingAll.value = false;
    }
}

function notificationAction(notification) {
    if (notification.type === 'pending_student_registration') {
        return notification.data?.action_url ?? '/admin/alunos';
    }

    return null;
}

function notificationActionLabel(notification) {
    if (notification.type === 'pending_student_registration') {
        return 'Ver cadastros pendentes';
    }

    return 'Abrir';
}

onMounted(loadNotifications);
</script>
