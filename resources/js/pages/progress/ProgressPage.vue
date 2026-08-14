<template>
    <AppLayout subtitle="Evolução">
        <div class="mb-4 flex flex-wrap gap-2">
            <UiButton
                v-for="option in periodOptions"
                :key="option.value"
                size="sm"
                :variant="period === option.value ? 'primary' : 'secondary'"
                @click="changePeriod(option.value)"
            >
                {{ option.label }}
            </UiButton>
        </div>

        <div v-if="loading" class="py-20 text-center text-slate-400">Carregando evolução...</div>
        <UiAlert v-else-if="error" :message="error" />

        <div v-else-if="summary" class="space-y-6">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <UiStat label="Maior carga" :value="`${summary.max_load ?? 0} kg`" />
                <UiStat label="Volume total" :value="formatNumber(summary.total_volume ?? 0)" />
                <UiStat label="Treinos" :value="summary.workout_count ?? 0" />
                <UiStat label="Peso atual" :value="formatWeight(summary.current_weight)" />
            </section>

            <UiCard title="Evolução de peso">
                <div v-if="summary.weight_evolution?.length" class="space-y-2">
                    <div
                        v-for="item in summary.weight_evolution"
                        :key="item.measured_at"
                        class="flex items-center justify-between rounded-lg bg-slate-950/60 px-3 py-2 text-sm"
                    >
                        <span>{{ formatDate(item.measured_at) }}</span>
                        <span class="text-slate-300">{{ formatWeight(item.weight) }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Sem medições registradas.</p>
            </UiCard>

            <UiCard title="Evolução por exercício">
                <div v-if="summary.exercise_evolution?.length" class="space-y-4">
                    <div
                        v-for="group in summary.exercise_evolution"
                        :key="group.exercise_id"
                        class="rounded-xl border border-slate-800 bg-slate-950/60 p-4"
                    >
                        <p class="font-medium text-white">{{ group.exercise_name }}</p>
                        <p class="mt-1 text-xs text-slate-400">
                            Máx: {{ group.max_load ?? 0 }} kg · Volume: {{ formatNumber(group.total_volume ?? 0) }}
                        </p>
                        <div v-if="group.entries?.length" class="mt-3 space-y-1">
                            <div
                                v-for="entry in group.entries.slice(0, 5)"
                                :key="`${entry.logged_at}-${entry.load}`"
                                class="flex justify-between text-xs text-slate-400"
                            >
                                <span>{{ formatDate(entry.logged_at) }}</span>
                                <span>{{ entry.load ?? 0 }} kg · {{ entry.repetitions ?? '—' }} reps</span>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Sem dados de exercícios.</p>
            </UiCard>

            <div class="flex flex-wrap gap-3">
                <RouterLink to="/medidas"><UiButton variant="secondary">Medidas</UiButton></RouterLink>
                <RouterLink to="/metas"><UiButton variant="secondary">Metas</UiButton></RouterLink>
                <RouterLink to="/fotos"><UiButton variant="secondary">Fotos</UiButton></RouterLink>
                <RouterLink to="/historico"><UiButton variant="ghost">Histórico de cargas</UiButton></RouterLink>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiStat from '../../components/ui/UiStat.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { formatDate, formatNumber, formatWeight } from '../../utils/format';

const summary = ref(null);
const loading = ref(true);
const error = ref('');
const period = ref('month');

const periodOptions = [
    { value: 'week', label: 'Semana' },
    { value: 'month', label: 'Mês' },
    { value: 'quarter', label: 'Trimestre' },
    { value: 'year', label: 'Ano' },
    { value: 'all', label: 'Tudo' },
];

async function loadSummary() {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/progress', { params: { period: period.value } });
        summary.value = extractData(response);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loading.value = false;
    }
}

function changePeriod(value) {
    period.value = value;
    loadSummary();
}

loadSummary();
</script>
