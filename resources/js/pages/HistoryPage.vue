<template>
    <AppLayout subtitle="Histórico de cargas">
        <div v-if="loading" class="py-20 text-center text-slate-400">Carregando histórico...</div>
        <UiAlert v-else-if="error" :message="error" />

        <div v-else class="space-y-6">
            <UiCard v-if="! logs.length" title="Nenhum registro">
                <p class="text-sm text-slate-400">Complete treinos para ver seu histórico aqui.</p>
            </UiCard>

            <UiCard
                v-for="group in groupedLogs"
                :key="group.date"
                :title="formatDate(group.date)"
                :subtitle="`${group.items.length} registros`"
            >
                <div class="space-y-3">
                    <div
                        v-for="log in group.items"
                        :key="log.id"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-950/60 px-3 py-2"
                    >
                        <div>
                            <p class="font-medium text-white">{{ log.exercise?.name ?? 'Exercício' }}</p>
                            <p class="text-xs text-slate-400">{{ formatDateTime(log.logged_at) }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 text-sm">
                            <UiBadge>Série {{ log.set_number }}</UiBadge>
                            <UiBadge variant="success">{{ log.repetitions }} reps</UiBadge>
                            <UiBadge variant="info">{{ log.load ?? 0 }} kg</UiBadge>
                        </div>
                    </div>
                </div>
            </UiCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api, { extractData, extractError } from '../api/client';
import UiAlert from '../components/ui/UiAlert.vue';
import UiBadge from '../components/ui/UiBadge.vue';
import UiCard from '../components/ui/UiCard.vue';
import AppLayout from '../layouts/AppLayout.vue';
import { formatDate, formatDateTime } from '../utils/format';

const logs = ref([]);
const loading = ref(true);
const error = ref('');

const groupedLogs = computed(() => {
    const groups = {};

    logs.value.forEach((log) => {
        const date = log.logged_at?.slice(0, 10) ?? 'unknown';

        if (! groups[date]) {
            groups[date] = [];
        }

        groups[date].push(log);
    });

    return Object.entries(groups)
        .sort(([a], [b]) => b.localeCompare(a))
        .map(([date, items]) => ({ date, items }));
});

onMounted(async () => {
    try {
        const response = await api.get('/history', { params: { per_page: 50 } });
        logs.value = extractData(response);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loading.value = false;
    }
});
</script>
