<template>
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-800 bg-slate-950/95 backdrop-blur md:hidden">
        <div class="mx-auto grid max-w-lg grid-cols-5 gap-1 px-2 py-2">
            <RouterLink
                v-for="item in items"
                :key="item.to"
                :to="item.to"
                class="relative flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[10px] font-medium transition"
                :class="isActive(item.to) ? 'bg-emerald-500/15 text-emerald-300' : 'text-slate-400'"
            >
                <span class="relative inline-flex">
                    <span class="text-base">{{ item.icon }}</span>
                    <NavNotificationBadge
                        v-if="item.to === '/notificacoes'"
                        :count="notifications.unreadCount"
                        floating
                    />
                </span>
                <span>{{ item.label }}</span>
            </RouterLink>
        </div>
    </nav>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import NavNotificationBadge from './NavNotificationBadge.vue';
import { useAuthStore } from '../stores/auth';
import { useNotificationsStore } from '../stores/notifications';

const route = useRoute();
const auth = useAuthStore();
const notifications = useNotificationsStore();

const items = computed(() => {
    if (auth.isTrainer && ! auth.isAdmin) {
        return [
            { to: '/', label: 'Início', icon: '🏠' },
            { to: '/professor/alunos', label: 'Alunos', icon: '🎓' },
            { to: '/professor/fichas/nova', label: 'Ficha', icon: '📋' },
            { to: '/notificacoes', label: 'Alertas', icon: '🔔' },
            { to: '/perfil', label: 'Perfil', icon: '👤' },
        ];
    }

    const base = [
        { to: '/', label: 'Início', icon: '🏠' },
        { to: '/treino', label: 'Treino', icon: '💪' },
        { to: '/historico', label: 'Histórico', icon: '📊' },
        { to: '/evolucao', label: 'Evolução', icon: '📈' },
        { to: '/perfil', label: 'Perfil', icon: '👤' },
    ];

    if (auth.isAdmin) {
        base.splice(1, 0, {
            to: '/admin',
            label: auth.isGymAdmin ? 'Academia' : 'Admin',
            icon: '⚙️',
        });
    }

    return base.slice(0, 5);
});

function isActive(path) {
    if (path === '/') {
        return route.path === '/';
    }

    return route.path.startsWith(path);
}
</script>
