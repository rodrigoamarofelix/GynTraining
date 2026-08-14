<template>
    <AppLayout subtitle="Detalhe do aluno">
        <div class="mb-6">
            <RouterLink to="/professor/alunos">
                <UiButton variant="ghost">← Alunos</UiButton>
            </RouterLink>
        </div>

        <div v-if="loading" class="py-20 text-center text-slate-400">Carregando...</div>
        <UiAlert v-else-if="error" :message="error" />
        <UiAlert v-if="success" :message="success" variant="success" />

        <div v-else-if="student" class="space-y-6">
            <UiCard :title="student.user?.name" :subtitle="student.user?.email">
                <div class="flex flex-wrap gap-2">
                    <UiBadge :variant="student.status === 'active' ? 'success' : 'warning'">
                        {{ profileStatusLabel(student.status) }}
                    </UiBadge>
                    <UiBadge>{{ student.gym?.name ?? 'Academia' }}</UiBadge>
                    <UiBadge v-if="student.trainer?.user?.name" variant="info">
                        Prof.: {{ student.trainer.user.name }}
                    </UiBadge>
                </div>
            </UiCard>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-white">Fichas de treino</h2>
                <RouterLink
                    v-if="scopeFilter === 'active'"
                    :to="{ name: 'trainer.workout.create', query: { student_id: student.id } }"
                >
                    <UiButton>Nova ficha</UiButton>
                </RouterLink>
            </div>

            <div class="flex flex-wrap gap-2">
                <UiButton
                    size="sm"
                    :variant="scopeFilter === 'active' ? 'primary' : 'secondary'"
                    @click="setScopeFilter('active')"
                >
                    Ativas
                </UiButton>
                <UiButton
                    size="sm"
                    :variant="scopeFilter === 'inactive' ? 'primary' : 'secondary'"
                    @click="setScopeFilter('inactive')"
                >
                    Excluídas
                </UiButton>
            </div>
            <p v-if="scopeFilter === 'inactive'" class="text-sm text-slate-400">
                Fichas excluídas logicamente permanecem no histórico e podem ser reativadas abaixo.
            </p>

            <div v-if="loadingPlans" class="text-sm text-slate-400">Carregando fichas...</div>

            <div v-else-if="plans.length" class="grid gap-4 md:grid-cols-2">
                <UiCard
                    v-for="plan in plans"
                    :key="plan.id"
                    :title="plan.name"
                    :subtitle="plan.description"
                >
                    <div class="flex flex-wrap gap-2">
                        <UiBadge variant="info">{{ profileStatusLabel(plan.status) }}</UiBadge>
                        <UiBadge v-if="plan.deleted_at" variant="danger">Excluída</UiBadge>
                    </div>
                    <p class="mt-2 text-sm text-slate-400">{{ plan.days?.length ?? 0 }} dias</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <RouterLink :to="{ name: 'trainer.workout.show', params: { id: plan.id } }">
                            <UiButton variant="secondary">
                                {{ plan.deleted_at ? 'Ver (excluída)' : 'Ver ficha' }}
                            </UiButton>
                        </RouterLink>
                        <RouterLink
                            v-if="! plan.deleted_at"
                            :to="{ name: 'trainer.workout.edit', params: { id: plan.id } }"
                        >
                            <UiButton variant="ghost">Editar</UiButton>
                        </RouterLink>
                        <UiButton
                            v-if="plan.deleted_at"
                            :loading="restoringId === plan.id"
                            @click="restorePlan(plan.id)"
                        >
                            Reativar ficha
                        </UiButton>
                    </div>
                </UiCard>
            </div>

            <UiCard v-else :title="scopeFilter === 'inactive' ? 'Nenhuma ficha excluída' : 'Sem fichas'">
                <p class="text-sm text-slate-400">
                    {{
                        scopeFilter === 'inactive'
                            ? 'Nenhuma ficha excluída logicamente.'
                            : 'Crie a primeira ficha para este aluno.'
                    }}
                </p>
            </UiCard>

            <UiCard title="Histórico recente">
                <div v-if="loadingHistory" class="text-sm text-slate-400">Carregando histórico...</div>
                <div v-else-if="history.length" class="space-y-3">
                    <div
                        v-for="entry in history"
                        :key="entry.id"
                        class="rounded-lg border border-slate-800 bg-slate-950/60 p-3 text-sm"
                    >
                        <p class="font-medium text-white">{{ entry.exercise?.name ?? 'Exercício' }}</p>
                        <p class="text-xs text-slate-400">
                            {{ formatDateTime(entry.logged_at ?? entry.created_at) }}
                            · Série {{ entry.set_number }}
                            · {{ entry.repetitions ?? '—' }} reps · {{ formatWeight(entry.load) }}
                        </p>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Nenhum treino registrado ainda.</p>
            </UiCard>

            <UiCard eyebrow="Evolução" title="Resumo de progresso">
                <div v-if="loadingProgress" class="text-sm text-slate-400">Carregando evolução...</div>
                <div v-else-if="progress" class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-lg bg-slate-950/60 p-3">
                            <p class="text-xs text-slate-500">Treinos</p>
                            <p class="text-lg font-semibold text-white">{{ progress.workout_count ?? 0 }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-950/60 p-3">
                            <p class="text-xs text-slate-500">Maior carga</p>
                            <p class="text-lg font-semibold text-white">{{ progress.max_load ?? 0 }} kg</p>
                        </div>
                        <div class="rounded-lg bg-slate-950/60 p-3">
                            <p class="text-xs text-slate-500">Volume total</p>
                            <p class="text-lg font-semibold text-white">{{ formatNumber(progress.total_volume ?? 0) }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-950/60 p-3">
                            <p class="text-xs text-slate-500">Peso atual</p>
                            <p class="text-lg font-semibold text-white">{{ formatWeight(progress.current_weight) }}</p>
                        </div>
                    </div>

                    <div v-if="goals.length" class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Metas ativas</p>
                        <div
                            v-for="goal in goals"
                            :key="goal.id"
                            class="rounded-lg border border-slate-800 bg-slate-950/60 p-3"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-white">{{ goal.name }}</p>
                                <UiBadge variant="success">{{ goal.progress_percentage ?? 0 }}%</UiBadge>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ goal.current_value }} / {{ goal.target }} {{ goal.unit ?? '' }}
                            </p>
                        </div>
                    </div>

                    <div v-if="latestMeasurement" class="rounded-lg border border-slate-800 bg-slate-950/60 p-3 text-sm">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Última medição</p>
                        <p class="mt-1 text-white">
                            {{ formatDate(latestMeasurement.measured_at) }}
                            · {{ formatWeight(latestMeasurement.weight) }}
                            <span v-if="latestMeasurement.height">· {{ latestMeasurement.height }} cm</span>
                        </p>
                    </div>

                    <div v-if="photos.length" class="grid grid-cols-3 gap-2">
                        <div
                            v-for="photo in photos.slice(0, 3)"
                            :key="photo.id"
                            class="aspect-[3/4] overflow-hidden rounded-lg bg-slate-900"
                        >
                            <img
                                v-if="photo.photo_url"
                                :src="photo.photo_url"
                                :alt="photo.category"
                                class="h-full w-full object-cover"
                            >
                        </div>
                    </div>
                    <p v-else-if="! goals.length && ! latestMeasurement" class="text-sm text-slate-400">
                        Sem dados de evolução além dos treinos.
                    </p>
                </div>
                <p v-else class="text-sm text-slate-400">Não foi possível carregar a evolução.</p>
            </UiCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { formatDate, formatDateTime, formatNumber, formatWeight, profileStatusLabel } from '../../utils/format';

const route = useRoute();
const student = ref(null);
const plans = ref([]);
const history = ref([]);
const progress = ref(null);
const goals = ref([]);
const photos = ref([]);
const latestMeasurement = ref(null);
const loading = ref(true);
const loadingPlans = ref(false);
const loadingHistory = ref(true);
const loadingProgress = ref(true);
const restoringId = ref(null);
const error = ref('');
const success = ref('');
const scopeFilter = ref('active');

async function loadPlans() {
    loadingPlans.value = true;

    try {
        const plansRes = await api.get('/workouts', {
            params: {
                student_id: route.params.id,
                scope: scopeFilter.value,
                per_page: 20,
            },
        });
        plans.value = extractData(plansRes);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loadingPlans.value = false;
    }
}

function setScopeFilter(value) {
    scopeFilter.value = value;
    success.value = '';
    loadPlans();
}

async function restorePlan(planId) {
    restoringId.value = planId;
    error.value = '';
    success.value = '';

    try {
        await api.post(`/workouts/${planId}/restore`);
        success.value = 'Ficha reativada com sucesso.';
        scopeFilter.value = 'active';
        await loadPlans();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        restoringId.value = null;
    }
}

onMounted(async () => {
    if (route.query.scope === 'inactive') {
        scopeFilter.value = 'inactive';
        success.value = 'Ficha excluída logicamente. Use o botão "Reativar ficha" para restaurá-la.';
    }

    try {
        const studentRes = await api.get(`/students/${route.params.id}`);
        student.value = extractData(studentRes);
        await loadPlans();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loading.value = false;
    }

    try {
        const historyRes = await api.get('/history', {
            params: { student_id: route.params.id, per_page: 5 },
        });
        history.value = extractData(historyRes);
    } catch {
        history.value = [];
    } finally {
        loadingHistory.value = false;
    }

    loadingProgress.value = true;

    try {
        const [progressRes, goalsRes, measurementsRes, photosRes] = await Promise.all([
            api.get('/progress', { params: { student_id: route.params.id } }),
            api.get('/goals', { params: { student_id: route.params.id, status: 'active', per_page: 5 } }),
            api.get('/body-measurements', { params: { student_id: route.params.id, per_page: 1 } }),
            api.get('/progress-photos', { params: { student_id: route.params.id, per_page: 3 } }),
        ]);

        progress.value = extractData(progressRes);
        goals.value = extractData(goalsRes);
        photos.value = extractData(photosRes);
        const measurements = extractData(measurementsRes);
        latestMeasurement.value = measurements[0] ?? null;
    } catch {
        progress.value = null;
    } finally {
        loadingProgress.value = false;
    }
});
</script>
