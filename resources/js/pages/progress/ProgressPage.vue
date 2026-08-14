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
                <LineChart
                    v-if="weightChart.labels.length"
                    :labels="weightChart.labels"
                    :datasets="weightChart.datasets"
                    y-suffix=" kg"
                />
                <p v-else class="text-sm text-slate-400">Sem medições registradas.</p>
            </UiCard>

            <UiCard title="Carga por exercício">
                <div v-if="summary.exercise_evolution?.length" class="space-y-4">
                    <UiSelect
                        v-model="selectedExerciseId"
                        label="Exercício"
                        :options="exerciseOptions"
                    />
                    <LineChart
                        v-if="exerciseChart.labels.length"
                        :labels="exerciseChart.labels"
                        :datasets="exerciseChart.datasets"
                        y-suffix=" kg"
                    />
                    <p v-else class="text-sm text-slate-400">Sem registros de carga para este exercício.</p>
                </div>
                <p v-else class="text-sm text-slate-400">Sem dados de exercícios.</p>
            </UiCard>

            <UiCard title="Resumo por exercício">
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
import { computed, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import LineChart from '../../components/charts/LineChart.vue';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiSelect from '../../components/ui/UiSelect.vue';
import UiStat from '../../components/ui/UiStat.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { chartColors, lineDataset } from '../../utils/chartTheme';
import { formatDate, formatNumber, formatWeight } from '../../utils/format';

const summary = ref(null);
const loading = ref(true);
const error = ref('');
const period = ref('month');
const selectedExerciseId = ref('');

const periodOptions = [
    { value: 'week', label: 'Semana' },
    { value: 'month', label: 'Mês' },
    { value: '3months', label: 'Trimestre' },
    { value: 'year', label: 'Ano' },
    { value: 'all', label: 'Tudo' },
];

const weightChart = computed(() => {
    const points = [...(summary.value?.weight_evolution ?? [])]
        .filter((item) => item.weight != null)
        .sort((a, b) => String(a.measured_at).localeCompare(String(b.measured_at)));

    return {
        labels: points.map((item) => formatDate(item.measured_at)),
        datasets: [lineDataset('Peso', points.map((item) => item.weight))],
    };
});

const exerciseOptions = computed(() =>
    (summary.value?.exercise_evolution ?? []).map((group) => ({
        value: String(group.exercise_id),
        label: group.exercise_name ?? 'Exercício',
    })),
);

const selectedExercise = computed(() =>
    (summary.value?.exercise_evolution ?? []).find(
        (group) => String(group.exercise_id) === selectedExerciseId.value,
    ) ?? null,
);

const exerciseChart = computed(() => {
    const entries = [...(selectedExercise.value?.entries ?? [])]
        .filter((entry) => entry.load != null)
        .sort((a, b) => String(a.logged_at).localeCompare(String(b.logged_at)));

    return {
        labels: entries.map((entry) => formatDate(entry.logged_at)),
        datasets: [lineDataset('Carga', entries.map((entry) => entry.load), chartColors.sky)],
    };
});

watch(exerciseOptions, (options) => {
    if (! options.length) {
        selectedExerciseId.value = '';
        return;
    }

    if (! options.some((option) => option.value === selectedExerciseId.value)) {
        selectedExerciseId.value = options[0].value;
    }
});

async function loadSummary() {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/progress', {
            params: { period: period.value === 'all' ? undefined : period.value },
        });
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
