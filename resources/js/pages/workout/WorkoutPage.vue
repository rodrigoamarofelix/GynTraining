<template>
    <AppLayout subtitle="Minha ficha">
        <div v-if="loading" class="py-20 text-center text-slate-400">Carregando ficha...</div>
        <UiAlert v-else-if="error" :message="error" />

        <div v-else-if="plan" class="space-y-6">
            <UiCard
                :title="plan.name"
                :subtitle="plan.description"
                eyebrow="Ficha ativa"
            >
                <div class="flex flex-wrap gap-2">
                    <UiBadge variant="success">{{ profileStatusLabel(plan.status) }}</UiBadge>
                    <UiBadge v-if="plan.start_date">Início: {{ formatDate(plan.start_date) }}</UiBadge>
                </div>
            </UiCard>

            <div class="grid gap-4 md:grid-cols-2">
                <UiCard
                    v-for="day in plan.days"
                    :key="day.id"
                    :title="day.name"
                    :subtitle="day.description"
                >
                    <p class="text-sm text-slate-400">
                        {{ day.exercises?.length ?? 0 }} exercícios
                    </p>
                    <RouterLink
                        :to="{ name: 'workout.execute', params: { planId: plan.id, dayId: day.id } }"
                        class="mt-4 inline-block"
                    >
                        <UiButton>Iniciar {{ day.name }}</UiButton>
                    </RouterLink>
                </UiCard>
            </div>
        </div>

        <UiCard v-else title="Nenhuma ficha encontrada">
            <p class="text-sm text-slate-400">
                Seu professor ainda não criou uma ficha de treino para você.
            </p>
        </UiCard>
    </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { formatDate, profileStatusLabel } from '../../utils/format';

const plan = ref(null);
const loading = ref(true);
const error = ref('');

onMounted(async () => {
    try {
        const response = await api.get('/workouts', { params: { status: 'active', per_page: 1 } });
        const items = extractData(response);

        if (items.length) {
            const detail = await api.get(`/workouts/${items[0].id}`);
            plan.value = extractData(detail);
        }
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loading.value = false;
    }
});
</script>
