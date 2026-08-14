<template>
    <AppLayout :subtitle="isEditing ? 'Editar ficha de treino' : 'Nova ficha de treino'">
        <div class="mb-6">
            <RouterLink :to="backLink">
                <UiButton variant="ghost">← Voltar</UiButton>
            </RouterLink>
        </div>

        <div v-if="loadingPlan" class="py-20 text-center text-slate-400">Carregando ficha...</div>

        <form v-else class="space-y-6" @submit.prevent="submit">
            <UiAlert v-if="error" :message="error" />
            <UiAlert v-if="success" :message="success" variant="success" />
            <UiAlert v-if="fieldErrors.exercises" :message="fieldErrors.exercises" />

            <UiCard title="Dados da ficha">
                <div class="grid gap-4 md:grid-cols-2">
                    <UiSelect
                        v-model="form.student_id"
                        label="Aluno *"
                        placeholder="Selecione o aluno"
                        :options="studentOptions"
                        :error="fieldErrors.student_id"
                        :disabled="isEditing"
                    />
                    <UiInput
                        v-model="form.name"
                        label="Nome da ficha *"
                        placeholder="Ficha ABC"
                        :error="fieldErrors.name"
                    />
                    <UiInput
                        v-model="form.description"
                        label="Descrição"
                        class="md:col-span-2"
                        placeholder="Objetivo do treino"
                    />
                    <UiSelect
                        v-if="isEditing"
                        v-model="form.status"
                        label="Status"
                        :options="statusOptions"
                    />
                </div>
            </UiCard>

            <UiCard
                v-for="(day, dayIndex) in form.days"
                :key="dayIndex"
                :title="`Dia ${dayIndex + 1}`"
                eyebrow="Treino"
            >
                <div class="mb-4 grid gap-4 md:grid-cols-2">
                    <UiInput v-model="day.name" label="Nome do dia" placeholder="Treino A" />
                    <UiInput v-model="day.description" label="Descrição" placeholder="Peito + Tríceps" />
                </div>

                <div
                    v-for="(exercise, exIndex) in day.exercises"
                    :key="exIndex"
                    class="mb-4 rounded-xl border border-slate-800 bg-slate-950/60 p-4"
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-300">Exercício {{ exIndex + 1 }}</p>
                        <UiButton
                            v-if="day.exercises.length > 1"
                            type="button"
                            variant="ghost"
                            @click="day.exercises.splice(exIndex, 1)"
                        >
                            Remover
                        </UiButton>
                    </div>
                    <UiSelect
                        v-model="exercise.exercise_id"
                        label="Exercício"
                        placeholder="Selecione"
                        :options="exerciseOptions"
                    />
                    <UiInput
                        v-model="exercise.rest_time"
                        label="Descanso (seg)"
                        type="number"
                        min="0"
                        class="mt-3"
                    />

                    <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">Séries</p>
                    <div
                        v-for="(set, setIndex) in exercise.sets"
                        :key="setIndex"
                        class="mt-2 grid gap-2 md:grid-cols-4"
                    >
                        <UiInput v-model="set.repetitions" label="Reps" type="number" min="1" />
                        <UiInput v-model="set.load" label="Carga (kg)" type="number" min="0" step="0.5" />
                        <UiInput v-model="set.rest_time" label="Descanso" type="number" min="0" />
                        <div class="flex items-end">
                            <UiButton
                                v-if="exercise.sets.length > 1"
                                type="button"
                                variant="ghost"
                                @click="exercise.sets.splice(setIndex, 1)"
                            >
                                Remover
                            </UiButton>
                        </div>
                    </div>
                    <UiButton
                        type="button"
                        variant="ghost"
                        class="mt-2"
                        @click="addSet(exercise)"
                    >
                        + Série
                    </UiButton>
                </div>

                <UiButton type="button" variant="secondary" @click="addExercise(day)">
                    + Exercício
                </UiButton>
            </UiCard>

            <div class="flex flex-wrap gap-3">
                <UiButton type="button" variant="secondary" @click="addDay">+ Dia de treino</UiButton>
                <UiButton type="submit" size="lg" :loading="saving">
                    {{ isEditing ? 'Salvar alterações' : 'Salvar ficha' }}
                </UiButton>
            </div>
        </form>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiInput from '../../components/ui/UiInput.vue';
import UiSelect from '../../components/ui/UiSelect.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { firstValidationError } from '../../utils/format';

const route = useRoute();
const router = useRouter();

const students = ref([]);
const exercises = ref([]);
const saving = ref(false);
const loadingPlan = ref(false);
const error = ref('');
const success = ref('');
const fieldErrors = reactive({});

const isEditing = computed(() => route.name === 'trainer.workout.edit');
const planId = computed(() => (isEditing.value ? Number(route.params.id) : null));

const form = reactive({
    student_id: route.query.student_id?.toString() ?? '',
    name: '',
    description: '',
    status: 'active',
    days: [createDay(1)],
});

const studentOptions = computed(() => students.value.map((s) => ({
    value: String(s.id),
    label: s.user?.name ?? `Aluno #${s.id}`,
})));

const exerciseOptions = computed(() => exercises.value.map((e) => ({
    value: String(e.id),
    label: e.name,
})));

const statusOptions = [
    { value: 'draft', label: 'Rascunho' },
    { value: 'active', label: 'Ativa' },
    { value: 'inactive', label: 'Inativa' },
    { value: 'completed', label: 'Concluída' },
];

const backLink = computed(() => {
    if (isEditing.value && form.student_id) {
        return { name: 'trainer.workout.show', params: { id: planId.value } };
    }

    if (form.student_id) {
        return { name: 'trainer.student', params: { id: form.student_id } };
    }

    return { name: 'trainer.students' };
});

function createDay(order) {
    return {
        name: `Treino ${String.fromCharCode(64 + order)}`,
        description: '',
        order,
        exercises: [createExercise()],
    };
}

function createExercise() {
    return {
        exercise_id: '',
        order: 1,
        rest_time: 60,
        sets: [
            { set_number: 1, repetitions: 12, load: 0, rest_time: 60 },
            { set_number: 2, repetitions: 10, load: 0, rest_time: 60 },
            { set_number: 3, repetitions: 8, load: 0, rest_time: 60 },
        ],
    };
}

function addDay() {
    form.days.push(createDay(form.days.length + 1));
}

function addExercise(day) {
    const exercise = createExercise();
    exercise.order = day.exercises.length + 1;
    day.exercises.push(exercise);
}

function addSet(exercise) {
    exercise.sets.push({
        set_number: exercise.sets.length + 1,
        repetitions: 10,
        load: 0,
        rest_time: exercise.rest_time || 60,
    });
}

function numericOrNull(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }

    return Number(value);
}

function suggestPlanName() {
    if (form.name.trim() || ! form.student_id || isEditing.value) {
        return;
    }

    const student = students.value.find((item) => String(item.id) === String(form.student_id));

    if (student?.user?.name) {
        form.name = `Ficha ${student.user.name}`;
    }
}

function populateFormFromPlan(plan) {
    form.student_id = String(plan.student_id ?? '');
    form.name = plan.name ?? '';
    form.description = plan.description ?? '';
    form.status = plan.status ?? 'active';
    form.days = (plan.days ?? []).map((day, dayIndex) => ({
        name: day.name ?? `Treino ${dayIndex + 1}`,
        description: day.description ?? '',
        order: day.order ?? dayIndex + 1,
        exercises: (day.exercises ?? []).map((exercise, exIndex) => ({
            exercise_id: String(exercise.exercise_id ?? exercise.exercise?.id ?? ''),
            order: exercise.order ?? exIndex + 1,
            rest_time: exercise.rest_time ?? 60,
            sets: (exercise.sets ?? []).map((set, setIndex) => ({
                set_number: set.set_number ?? setIndex + 1,
                repetitions: set.repetitions ?? 10,
                load: set.load ?? 0,
                rest_time: set.rest_time ?? exercise.rest_time ?? 60,
            })),
        })),
    }));

    if (! form.days.length) {
        form.days = [createDay(1)];
    }
}

function validateForm() {
    Object.keys(fieldErrors).forEach((key) => delete fieldErrors[key]);

    if (! form.student_id) {
        fieldErrors.student_id = 'Selecione o aluno.';
    }

    if (! form.name.trim()) {
        fieldErrors.name = 'Informe o nome da ficha.';
    }

    const hasExercise = form.days.some((day) => day.exercises.some((item) => item.exercise_id));

    if (! hasExercise) {
        fieldErrors.exercises = 'Adicione pelo menos um exercício.';
    }

    return Object.keys(fieldErrors).length === 0;
}

function buildPayload() {
    const payload = {
        student_id: Number(form.student_id),
        name: form.name.trim(),
        description: form.description.trim() || undefined,
        days: form.days.map((day, dayIndex) => ({
            name: day.name,
            description: day.description || undefined,
            order: dayIndex + 1,
            exercises: day.exercises
                .filter((ex) => ex.exercise_id)
                .map((ex, exIndex) => ({
                    exercise_id: Number(ex.exercise_id),
                    order: exIndex + 1,
                    rest_time: Number(ex.rest_time) || 60,
                    sets: ex.sets.map((set, setIndex) => ({
                        set_number: setIndex + 1,
                        repetitions: numericOrNull(set.repetitions),
                        load: numericOrNull(set.load),
                        rest_time: numericOrNull(set.rest_time) ?? (Number(ex.rest_time) || 60),
                    })),
                })),
        })),
    };

    if (isEditing.value) {
        payload.status = form.status;
    }

    return payload;
}

async function submit() {
    if (! validateForm()) {
        error.value = 'Corrija os campos destacados antes de salvar.';

        return;
    }

    saving.value = true;
    error.value = '';
    success.value = '';

    try {
        const payload = buildPayload();

        if (isEditing.value) {
            const response = await api.put(`/workouts/${planId.value}`, payload);
            const plan = extractData(response);
            success.value = 'Ficha atualizada com sucesso!';
            setTimeout(() => {
                router.push({ name: 'trainer.workout.show', params: { id: plan.id } });
            }, 600);
        } else {
            const response = await api.post('/workouts', payload);
            const plan = extractData(response);
            success.value = 'Ficha criada com sucesso!';
            setTimeout(() => {
                router.push({ name: 'trainer.workout.show', params: { id: plan.id } });
            }, 600);
        }
    } catch (err) {
        const parsed = extractError(err);
        Object.assign(fieldErrors, parsed.errors ?? {});
        error.value = firstValidationError(parsed.errors) ?? parsed.message;
    } finally {
        saving.value = false;
    }
}

async function loadPlan() {
    if (! isEditing.value || ! planId.value) {
        return;
    }

    loadingPlan.value = true;
    error.value = '';

    try {
        const response = await api.get(`/workouts/${planId.value}`);
        const data = extractData(response);

        if (data.deleted_at) {
            error.value = 'Esta ficha está excluída logicamente. Reative-a antes de editar.';
            return;
        }

        populateFormFromPlan(data);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loadingPlan.value = false;
    }
}

onMounted(async () => {
    try {
        const [studentsRes, exercisesRes] = await Promise.all([
            api.get('/students', { params: { per_page: 50 } }),
            api.get('/exercises', { params: { per_page: 100 } }),
        ]);

        students.value = extractData(studentsRes);
        exercises.value = extractData(exercisesRes);
        suggestPlanName();
        await loadPlan();
    } catch (err) {
        error.value = extractError(err).message;
    }
});
</script>
