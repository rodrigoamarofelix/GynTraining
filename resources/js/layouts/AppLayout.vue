<template>
    <div class="min-h-dvh bg-slate-950">
        <header class="sticky top-0 z-20 border-b border-slate-800 bg-slate-950/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
                <div>
                    <RouterLink to="/" class="text-lg font-bold text-white">
                        Gyn<span class="text-emerald-400">Training</span>
                    </RouterLink>
                    <p v-if="subtitle" class="text-xs text-slate-400">{{ subtitle }}</p>
                </div>
                <nav class="hidden items-center gap-1 md:flex">
                    <RouterLink
                        v-for="item in desktopNav"
                        :key="item.to"
                        :to="item.to"
                        class="relative inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="isActive(item.to) ? 'bg-emerald-500/15 text-emerald-300' : 'text-slate-400 hover:text-white'"
                    >
                        {{ item.label }}
                        <NavNotificationBadge
                            v-if="item.to === '/notificacoes'"
                            :count="notifications.unreadCount"
                        />
                    </RouterLink>
                </nav>
                <div class="hidden items-center gap-3 md:flex">
                    <span class="text-sm text-slate-400">{{ auth.displayName }}</span>
                    <UiButton variant="ghost" @click="logout">Sair</UiButton>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 pb-28 md:pb-8">
            <slot />
        </main>

        <AppNav />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import AppNav from '../components/AppNav.vue';
import NavNotificationBadge from '../components/NavNotificationBadge.vue';
import UiButton from '../components/ui/UiButton.vue';
import { useAuthStore } from '../stores/auth';
import { useNotificationsStore } from '../stores/notifications';

defineProps({
    subtitle: String,
});

const auth = useAuthStore();
const notifications = useNotificationsStore();
const route = useRoute();
const router = useRouter();

const desktopNav = computed(() => {
    if (auth.isTrainer && ! auth.isAdmin) {
        return [
            { to: '/', label: 'Dashboard' },
            { to: '/professor/alunos', label: 'Alunos' },
            { to: '/professor/fichas/nova', label: 'Nova ficha' },
            { to: '/notificacoes', label: 'Notificações' },
            { to: '/perfil', label: 'Perfil' },
        ];
    }

    const items = [
        { to: '/', label: 'Dashboard' },
        { to: '/treino', label: 'Treino' },
        { to: '/historico', label: 'Histórico' },
        { to: '/evolucao', label: 'Evolução' },
        { to: '/exercicios', label: 'Exercícios' },
        { to: '/notificacoes', label: 'Notificações' },
        { to: '/perfil', label: 'Perfil' },
    ];

    if (auth.isTrainer) {
        items.splice(1, 0, { to: '/professor/alunos', label: 'Alunos' });
    }

    if (auth.isAdmin) {
        items.splice(1, 0, {
            to: '/admin',
            label: auth.isGymAdmin ? 'Academia' : 'Admin',
        });
    }

    return items;
});

function isActive(path) {
    if (path === '/') {
        return route.path === '/';
    }

    return route.path.startsWith(path);
}

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>
