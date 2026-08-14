<template>
    <RouterView />
</template>

<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import { RouterView } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { useNotificationsStore } from './stores/notifications';

const auth = useAuthStore();
const notifications = useNotificationsStore();

function syncNotifications() {
    if (auth.isAuthenticated) {
        notifications.fetchUnreadCount();
        notifications.startPolling();
        return;
    }

    notifications.stopPolling();
    notifications.reset();
}

function onVisibilityChange() {
    if (document.visibilityState === 'visible' && auth.isAuthenticated) {
        notifications.fetchUnreadCount();
    }
}

watch(() => auth.isAuthenticated, syncNotifications, { immediate: true });

onMounted(() => {
    document.addEventListener('visibilitychange', onVisibilityChange);
});

onUnmounted(() => {
    document.removeEventListener('visibilitychange', onVisibilityChange);
    notifications.stopPolling();
});
</script>
