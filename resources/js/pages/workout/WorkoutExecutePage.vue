<template>
    <AppLayout subtitle="Execução do treino">
        <div v-if="loading" class="py-20 text-center text-slate-400">Preparando treino...</div>
        <UiAlert v-else-if="error" :message="error" />

        <div v-else-if="sessionConflict" class="space-y-4">
            <UiCard title="Treino em andamento" subtitle="Você já tem uma sessão ativa">
                <p class="text-sm text-slate-400">
                    {{ sessionConflict.workout_day?.name ?? 'Treino' }}
                    · {{ sessionConflict.workout_plan?.name ?? 'Ficha' }}
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <UiButton @click="continueActiveSession">Continuar treino ativo</UiButton>
                    <UiButton variant="danger" :loading="cancelling" @click="cancelActiveSession">
                        Cancelar e iniciar este
                    </UiButton>
                </div>
            </UiCard>
        </div>

        <div v-else class="space-y-6">
            <UiCard
                :title="day?.name ?? 'Treino'"
                :subtitle="plan?.name"
                eyebrow="Sessão ativa"
            >
                <div class="flex flex-wrap gap-3">
                    <UiBadge variant="info">{{ profileStatusLabel(session?.status ?? 'in_progress') }}</UiBadge>
                    <UiBadge v-if="session?.started_at">Início: {{ formatDateTime(session.started_at) }}</UiBadge>
                    <UiBadge>{{ exerciseIndex + 1 }} / {{ exercises.length }} exercícios</UiBadge>
                </div>
            </UiCard>

            <UiCard
                v-if="currentExercise"
                :title="currentExercise.exercise?.name ?? 'Exercício'"
                :subtitle="`Série ${nextSetNumber} de ${plannedSetsCount}`"
            >
                <form class="grid gap-4 md:grid-cols-3" @submit.prevent="logSet">
                    <UiInput
                        v-model="setForm.repetitions"
                        label="Repetições"
                        type="number"
                        min="1"
                        required
                    />
                    <UiInput
                        v-model="setForm.load"
                        label="Carga (kg)"
                        type="number"
                        min="0"
                        step="0.5"
                    />
                    <UiInput
                        v-model="setForm.notes"
                        label="Observação"
                        placeholder="Opcional"
                    />
                    <div class="md:col-span-3">
                        <UiButton type="submit" size="lg" :loading="logging" :disabled="allSetsLogged">
                            {{ allSetsLogged ? 'Séries concluídas' : 'Registrar série' }}
                        </UiButton>
                    </div>
                </form>

                <div v-if="loggedSetsForCurrent.length" class="mt-6 space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Registradas</p>
                    <div
                        v-for="log in loggedSetsForCurrent"
                        :key="log.id ?? `${log.set_number}-${log.logged_at}`"
                        class="flex items-center justify-between rounded-lg border border-emerald-500/20 bg-emerald-500/5 px-3 py-2 text-sm"
                    >
                        <span>Série {{ log.set_number }}</span>
                        <span class="text-slate-300">
                            {{ log.repetitions }} reps · {{ log.load ?? 0 }} kg
                        </span>
                    </div>
                </div>

                <div v-if="currentExercise.sets?.length" class="mt-6 space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Planejado</p>
                    <div
                        v-for="set in currentExercise.sets"
                        :key="set.id"
                        class="flex items-center justify-between rounded-lg bg-slate-950/60 px-3 py-2 text-sm"
                        :class="isSetLogged(set.set_number) ? 'opacity-50' : ''"
                    >
                        <span>Série {{ set.set_number }}</span>
                        <span class="text-slate-400">
                            {{ set.repetitions }} reps · {{ set.load ?? 0 }} kg · {{ set.rest_time ?? currentExercise.rest_time ?? 60 }}s
                        </span>
                    </div>
                </div>
            </UiCard>

            <UiCard v-else title="Treino concluído nesta sessão">
                <p class="text-sm text-slate-400">Todos os exercícios foram registrados. Finalize o treino.</p>
            </UiCard>

            <div class="flex flex-wrap gap-3">
                <UiButton
                    v-if="exerciseIndex > 0"
                    variant="ghost"
                    @click="exerciseIndex--"
                >
                    Exercício anterior
                </UiButton>
                <UiButton
                    v-if="exerciseIndex < exercises.length - 1"
                    variant="secondary"
                    :disabled="! canAdvanceExercise"
                    @click="exerciseIndex++"
                >
                    Próximo exercício
                </UiButton>
                <UiButton variant="danger" :loading="finishing" @click="finishWorkout">
                    Finalizar treino
                </UiButton>
                <UiButton variant="ghost" :loading="cancelling" @click="cancelWorkout">
                    Cancelar treino
                </UiButton>
            </div>
        </div>

        <RestTimer
            :active="restActive"
            :seconds="restSeconds"
            @finished="onRestFinished"
            @skip="restActive = false"
        />
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import RestTimer from '../../components/RestTimer.vue';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiInput from '../../components/ui/UiInput.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { formatDateTime, profileStatusLabel } from '../../utils/format';

const route = useRoute();
const router = useRouter();

const plan = ref(null);
const day = ref(null);
const session = ref(null);
const sessionConflict = ref(null);
const loading = ref(true);
const error = ref('');
const logging = ref(false);
const finishing = ref(false);
const cancelling = ref(false);
const exerciseIndex = ref(0);
const restActive = ref(false);
const restSeconds = ref(60);

const setForm = reactive({
    repetitions: '',
    load: '',
    notes: '',
});

const exercises = computed(() => day.value?.exercises ?? []);
const currentExercise = computed(() => exercises.value[exerciseIndex.value] ?? null);
const plannedSetsCount = computed(() => currentExercise.value?.sets?.length ?? 1);

const loggedSetsForCurrent = computed(() => {
    if (! session.value?.exercise_logs || ! currentExercise.value) {
        return [];
    }

    return session.value.exercise_logs
        .filter((log) => log.exercise_id === currentExercise.value.exercise_id)
        .sort((a, b) => a.set_number - b.set_number);
});

const nextSetNumber = computed(() => {
    if (! loggedSetsForCurrent.value.length) {
        return 1;
    }

    const maxLogged = Math.max(...loggedSetsForCurrent.value.map((log) => log.set_number));

    return Math.min(maxLogged + 1, plannedSetsCount.value);
});

const allSetsLogged = computed(() => loggedSetsForCurrent.value.length >= plannedSetsCount.value);

const canAdvanceExercise = computed(() => allSetsLogged.value || loggedSetsForCurrent.value.length > 0);

function isSetLogged(setNumber) {
    return loggedSetsForCurrent.value.some((log) => log.set_number === setNumber);
}

function prefillFromPlan() {
    const plannedSet = currentExercise.value?.sets?.find((set) => set.set_number === nextSetNumber.value);

    setForm.repetitions = plannedSet?.repetitions != null ? String(plannedSet.repetitions) : '';
    setForm.load = plannedSet?.load != null ? String(plannedSet.load) : '';
    setForm.notes = '';
}

async function loadPlan() {
    const planResponse = await api.get(`/workouts/${route.params.planId}`);
    plan.value = extractData(planResponse);
    day.value = plan.value.days?.find((item) => String(item.id) === String(route.params.dayId)) ?? null;

    if (! day.value) {
        throw new Error('Dia de treino não encontrado nesta ficha.');
    }
}

async function loadActiveSession() {
    const sessionsResponse = await api.get('/workout-sessions', {
        params: {
            status: 'in_progress',
            workout_plan_id: route.params.planId,
            per_page: 1,
        },
    });

    const items = extractData(sessionsResponse);

    if (! items.length) {
        return null;
    }

    const detail = await api.get(`/workout-sessions/${items[0].id}`);

    return extractData(detail);
}

async function ensureSession() {
    try {
        const startResponse = await api.post(`/workouts/${route.params.planId}/start`, {
            workout_day_id: Number(route.params.dayId),
        });
        session.value = extractData(startResponse);
    } catch (err) {
        const parsed = extractError(err);

        if (parsed.status !== 422 || ! parsed.errors?.session) {
            throw err;
        }

        const activeSession = await loadActiveSession();

        if (! activeSession) {
            throw err;
        }

        const samePlan = String(activeSession.workout_plan_id) === String(route.params.planId);
        const sameDay = String(activeSession.workout_day_id) === String(route.params.dayId);

        if (samePlan && sameDay) {
            session.value = activeSession;

            return;
        }

        sessionConflict.value = activeSession;
    }
}

async function continueActiveSession() {
    const active = sessionConflict.value;

    if (! active) {
        return;
    }

    router.replace({
        name: 'workout.execute',
        params: {
            planId: active.workout_plan_id,
            dayId: active.workout_day_id,
        },
    });
}

async function cancelActiveSession() {
    if (! sessionConflict.value?.id) {
        return;
    }

    cancelling.value = true;
    error.value = '';

    try {
        await api.post(`/workout-sessions/${sessionConflict.value.id}/cancel`, {
            notes: 'Cancelado para iniciar outro treino',
        });

        sessionConflict.value = null;
        await ensureSession();
        syncExerciseIndex();
        prefillFromPlan();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        cancelling.value = false;
    }
}

async function refreshSession() {
    if (! session.value?.id) {
        return;
    }

    const detail = await api.get(`/workout-sessions/${session.value.id}`);
    session.value = extractData(detail);
}

function syncExerciseIndex() {
    if (! session.value?.exercise_logs?.length || ! exercises.value.length) {
        return;
    }

    const firstIncomplete = exercises.value.findIndex((exercise) => {
        const logged = session.value.exercise_logs.filter((log) => log.exercise_id === exercise.exercise_id).length;
        const planned = exercise.sets?.length ?? 1;

        return logged < planned;
    });

    exerciseIndex.value = firstIncomplete >= 0 ? firstIncomplete : exercises.value.length - 1;
}

async function logSet() {
    if (! session.value || ! currentExercise.value || allSetsLogged.value) {
        return;
    }

    const repetitions = Number(setForm.repetitions);

    if (! repetitions || repetitions < 1) {
        error.value = 'Informe as repetições da série.';

        return;
    }

    logging.value = true;
    error.value = '';

    try {
        await api.post('/workout-sessions', {
            workout_session_id: session.value.id,
            exercise_id: currentExercise.value.exercise_id,
            set_number: nextSetNumber.value,
            repetitions,
            load: setForm.load !== '' ? Number(setForm.load) : undefined,
            notes: setForm.notes || undefined,
        });

        const plannedSet = currentExercise.value.sets?.find((set) => set.set_number === nextSetNumber.value);
        restSeconds.value = plannedSet?.rest_time
            ?? currentExercise.value.rest_time
            ?? 60;
        restActive.value = true;

        await refreshSession();
        prefillFromPlan();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        logging.value = false;
    }
}

function onRestFinished() {
    restActive.value = false;

    if (allSetsLogged.value && exerciseIndex.value < exercises.value.length - 1) {
        exerciseIndex.value++;
    }
}

async function finishWorkout() {
    if (! window.confirm('Finalizar este treino?')) {
        return;
    }

    finishing.value = true;
    error.value = '';

    try {
        await api.post(`/workouts/${route.params.planId}/finish`);
        router.push('/');
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        finishing.value = false;
    }
}

async function cancelWorkout() {
    if (! session.value?.id) {
        return;
    }

    if (! window.confirm('Cancelar este treino? Os registros desta sessão serão descartados.')) {
        return;
    }

    cancelling.value = true;
    error.value = '';

    try {
        await api.post(`/workout-sessions/${session.value.id}/cancel`, {
            notes: 'Cancelado pelo aluno',
        });
        router.push('/treino');
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        cancelling.value = false;
    }
}

watch(currentExercise, () => prefillFromPlan());
watch(exerciseIndex, () => prefillFromPlan());

onMounted(async () => {
    try {
        await loadPlan();
        await ensureSession();

        if (! sessionConflict.value && session.value) {
            syncExerciseIndex();
            prefillFromPlan();
        }
    } catch (err) {
        error.value = extractError(err).message || err.message;
    } finally {
        loading.value = false;
    }
});
</script>
