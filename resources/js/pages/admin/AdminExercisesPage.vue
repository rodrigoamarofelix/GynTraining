<template>
    <AppLayout subtitle="Gerenciar exercícios">
        <div class="mb-6">
            <RouterLink to="/admin">
                <UiButton variant="ghost">← Admin</UiButton>
            </RouterLink>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-6">
                <UiCard :title="isEditing ? 'Editar exercício' : 'Novo exercício'">
                    <p v-if="! isEditing" class="mb-4 text-sm text-slate-400">
                        Deixe academia vazia para exercício global do catálogo.
                    </p>
                    <p v-else-if="isTrashed" class="mb-4 text-sm text-amber-300">
                        Exercício excluído logicamente. Use reativar para restaurar.
                    </p>
                    <form class="space-y-4" @submit.prevent="submit">
                        <UiAlert v-if="error" :message="error" />
                        <UiAlert v-if="success" :message="success" variant="success" />
                        <UiInput
                            v-model="form.name"
                            label="Nome"
                            placeholder="Supino reto"
                            :error="fieldErrors.name"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.description"
                            label="Descrição"
                            :error="fieldErrors.description"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.instructions"
                            label="Instruções"
                            :error="fieldErrors.instructions"
                            :disabled="isTrashed"
                        />
                        <UiSelect
                            v-model="form.muscle_group_id"
                            label="Grupo muscular"
                            placeholder="Selecione"
                            :options="muscleGroupOptions"
                            :error="fieldErrors.muscle_group_id"
                            :disabled="isTrashed"
                        />
                        <UiSelect
                            v-model="form.exercise_category_id"
                            label="Categoria"
                            placeholder="Selecione"
                            :options="categoryOptions"
                            :error="fieldErrors.exercise_category_id"
                            :disabled="isTrashed"
                        />
                        <UiSelect
                            v-model="form.gym_id"
                            label="Academia (opcional)"
                            placeholder="Global — todas as academias"
                            :options="gymOptions"
                            :error="fieldErrors.gym_id"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.equipment"
                            label="Equipamento"
                            placeholder="Barra, halteres..."
                            :error="fieldErrors.equipment"
                            :disabled="isTrashed"
                        />
                        <UiSelect
                            v-model="form.difficulty"
                            label="Dificuldade"
                            :options="difficultyOptions"
                            :error="fieldErrors.difficulty"
                            :disabled="isTrashed"
                        />
                        <UiSelect
                            v-if="isEditing && ! isTrashed"
                            v-model="form.status"
                            label="Status"
                            :options="statusOptions"
                            :error="fieldErrors.status"
                        />
                        <div class="flex flex-wrap gap-3">
                            <UiButton v-if="! isTrashed" type="submit" :loading="saving">
                                {{ isEditing ? 'Salvar alterações' : 'Cadastrar exercício' }}
                            </UiButton>
                            <UiButton
                                v-if="isEditing && (isTrashed || form.status === 'inactive')"
                                type="button"
                                :loading="restoring"
                                @click="restoreExercise"
                            >
                                Reativar exercício
                            </UiButton>
                            <UiButton v-if="isEditing" type="button" variant="secondary" @click="cancelEdit">
                                Cancelar
                            </UiButton>
                            <UiButton
                                v-if="isEditing && ! isTrashed"
                                type="button"
                                variant="danger"
                                :loading="deleting"
                                @click="removeExercise"
                            >
                                Excluir exercício
                            </UiButton>
                        </div>
                    </form>
                </UiCard>

                <UiCard v-if="isEditing" title="Histórico de alterações">
                    <div v-if="loadingActivity" class="text-sm text-slate-400">Carregando histórico...</div>
                    <div v-else-if="activityLogs.length" class="max-h-80 space-y-3 overflow-y-auto">
                        <div
                            v-for="log in activityLogs"
                            :key="log.id"
                            class="rounded-lg border border-slate-800 bg-slate-950/60 p-3 text-sm"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <UiBadge variant="info">{{ exerciseActivityActionLabel(log.action) }}</UiBadge>
                                <span class="text-xs text-slate-500">{{ formatDateTime(log.created_at) }}</span>
                            </div>
                            <p class="mt-2 text-slate-300">{{ log.summary }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Por: {{ log.performer?.name ?? 'Sistema' }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400">Nenhuma alteração registrada.</p>
                </UiCard>
            </div>

            <UiCard title="Exercícios cadastrados">
                <div class="mb-4 flex flex-wrap gap-2">
                    <UiButton
                        size="sm"
                        :variant="scopeFilter === 'active' ? 'primary' : 'secondary'"
                        @click="setScopeFilter('active')"
                    >
                        Ativos
                    </UiButton>
                    <UiButton
                        size="sm"
                        :variant="scopeFilter === 'inactive' ? 'primary' : 'secondary'"
                        @click="setScopeFilter('inactive')"
                    >
                        Inativos
                    </UiButton>
                </div>
                <UiInput
                    v-model="search"
                    label="Buscar"
                    placeholder="Nome do exercício..."
                    @input="debouncedLoad"
                />
                <div v-if="loading" class="mt-4 text-sm text-slate-400">Carregando...</div>
                <div v-else-if="exercises.length" class="mt-4 max-h-[32rem] space-y-3 overflow-y-auto">
                    <div
                        v-for="exercise in exercises"
                        :key="exercise.id"
                        class="cursor-pointer rounded-xl border bg-slate-950/60 p-4 transition hover:border-slate-700"
                        :class="selectedId === exercise.id
                            ? 'border-emerald-500/60 ring-1 ring-emerald-500/30'
                            : 'border-slate-800'"
                        @click="selectExercise(exercise)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-white">{{ exercise.name }}</p>
                                <p class="text-sm text-slate-400">
                                    {{ exercise.muscle_group?.name ?? '—' }} ·
                                    {{ exercise.category?.name ?? '—' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ exercise.gym?.name ?? 'Global' }} ·
                                    {{ exerciseDifficultyLabel(exercise.difficulty) }}
                                </p>
                            </div>
                            <UiBadge :variant="exerciseBadgeVariant(exercise)">
                                {{ exerciseBadgeLabel(exercise) }}
                            </UiBadge>
                        </div>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Nenhum exercício encontrado.</p>
            </UiCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiInput from '../../components/ui/UiInput.vue';
import UiSelect from '../../components/ui/UiSelect.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useAuthStore } from '../../stores/auth';
import {
    exerciseActivityActionLabel,
    exerciseDifficultyLabel,
    formatDateTime,
    profileStatusLabel,
} from '../../utils/format';

const auth = useAuthStore();

const exercises = ref([]);
const muscleGroups = ref([]);
const categories = ref([]);
const gyms = ref([]);
const activityLogs = ref([]);
const loading = ref(true);
const loadingActivity = ref(false);
const saving = ref(false);
const deleting = ref(false);
const restoring = ref(false);
const selectedId = ref(null);
const selectedExercise = ref(null);
const error = ref('');
const success = ref('');
const search = ref('');
const scopeFilter = ref('active');
const fieldErrors = reactive({});
let debounceTimer = null;

const form = reactive({
    name: '',
    description: '',
    instructions: '',
    muscle_group_id: '',
    exercise_category_id: '',
    gym_id: '',
    equipment: '',
    difficulty: 'beginner',
    status: 'active',
});

const isEditing = computed(() => selectedId.value !== null);
const isTrashed = computed(() => Boolean(selectedExercise.value?.deleted_at));

const muscleGroupOptions = computed(() => muscleGroups.value.map((group) => ({
    value: String(group.id),
    label: group.name,
})));

const categoryOptions = computed(() => categories.value.map((category) => ({
    value: String(category.id),
    label: category.name,
})));

const gymOptions = computed(() => {
    const gymItems = gyms.value.map((gym) => ({
        value: String(gym.id),
        label: gym.name,
    }));

    if (auth.isGymAdmin) {
        return gymItems;
    }

    return [
        { value: '', label: 'Global — todas as academias' },
        ...gymItems,
    ];
});

const difficultyOptions = [
    { value: 'beginner', label: 'Iniciante' },
    { value: 'intermediate', label: 'Intermediário' },
    { value: 'advanced', label: 'Avançado' },
];

const statusOptions = [
    { value: 'active', label: 'Ativo' },
    { value: 'inactive', label: 'Inativo' },
];

function exerciseBadgeLabel(exercise) {
    if (exercise.deleted_at) {
        return 'Excluído';
    }

    return profileStatusLabel(exercise.status);
}

function exerciseBadgeVariant(exercise) {
    if (exercise.deleted_at) {
        return 'danger';
    }

    if (exercise.status === 'active') {
        return 'success';
    }

    return 'default';
}

function clearFieldErrors() {
    Object.keys(fieldErrors).forEach((key) => delete fieldErrors[key]);
}

function resetForm() {
    selectedId.value = null;
    selectedExercise.value = null;
    activityLogs.value = [];
    form.name = '';
    form.description = '';
    form.instructions = '';
    form.muscle_group_id = '';
    form.exercise_category_id = '';
    form.gym_id = '';
    form.equipment = '';
    form.difficulty = 'beginner';
    form.status = 'active';
}

function cancelEdit() {
    error.value = '';
    success.value = '';
    clearFieldErrors();
    resetForm();
}

async function loadActivityLogs(exerciseId) {
    loadingActivity.value = true;

    try {
        const response = await api.get(`/exercises/${exerciseId}/activity-logs`, { params: { per_page: 30 } });
        activityLogs.value = extractData(response);
    } catch {
        activityLogs.value = [];
    } finally {
        loadingActivity.value = false;
    }
}

function selectExercise(exercise) {
    error.value = '';
    success.value = '';
    clearFieldErrors();
    selectedId.value = exercise.id;
    selectedExercise.value = exercise;
    form.name = exercise.name ?? '';
    form.description = exercise.description ?? '';
    form.instructions = exercise.instructions ?? '';
    form.muscle_group_id = String(exercise.muscle_group_id ?? '');
    form.exercise_category_id = String(exercise.exercise_category_id ?? '');
    form.gym_id = exercise.gym_id ? String(exercise.gym_id) : '';
    form.equipment = exercise.equipment ?? '';
    form.difficulty = exercise.difficulty ?? 'beginner';
    form.status = exercise.deleted_at ? 'inactive' : (exercise.status ?? 'active');
    loadActivityLogs(exercise.id);
}

function setScopeFilter(value) {
    scopeFilter.value = value;
    loadExercises();
}

async function loadCatalogs() {
    const [groupsRes, categoriesRes, gymsRes] = await Promise.all([
        api.get('/muscle-groups', { params: { per_page: 100, scope: 'active' } }),
        api.get('/exercise-categories', { params: { per_page: 100, scope: 'active' } }),
        api.get('/gyms', { params: { per_page: 50, scope: 'active' } }),
    ]);

    muscleGroups.value = extractData(groupsRes);
    categories.value = extractData(categoriesRes);
    gyms.value = extractData(gymsRes);

    if (auth.isGymAdmin && gyms.value.length === 1 && ! form.gym_id) {
        form.gym_id = String(gyms.value[0].id);
    }
}

async function loadExercises() {
    loading.value = true;

    try {
        const response = await api.get('/exercises', {
            params: {
                search: search.value || undefined,
                scope: scopeFilter.value,
                per_page: 50,
            },
        });
        exercises.value = extractData(response);

        if (selectedId.value) {
            const current = exercises.value.find((exercise) => exercise.id === selectedId.value);

            if (current) {
                selectExercise(current);
            } else if (scopeFilter.value !== 'inactive') {
                resetForm();
            }
        }
    } finally {
        loading.value = false;
    }
}

function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadExercises, 300);
}

function buildPayload() {
    const gymId = auth.isGymAdmin
        ? Number(form.gym_id || gyms.value[0]?.id)
        : (form.gym_id ? Number(form.gym_id) : null);

    const payload = {
        name: form.name,
        description: form.description || null,
        instructions: form.instructions || null,
        muscle_group_id: Number(form.muscle_group_id),
        exercise_category_id: Number(form.exercise_category_id),
        gym_id: gymId,
        equipment: form.equipment || null,
        difficulty: form.difficulty,
    };

    if (isEditing.value) {
        payload.status = form.status;
    }

    return payload;
}

async function submit() {
    if (isTrashed.value) {
        return;
    }

    saving.value = true;
    error.value = '';
    success.value = '';
    clearFieldErrors();

    try {
        const payload = buildPayload();

        if (isEditing.value) {
            await api.put(`/exercises/${selectedId.value}`, payload);
            success.value = 'Exercício atualizado com sucesso.';
            await loadActivityLogs(selectedId.value);
        } else {
            await api.post('/exercises', payload);
            success.value = 'Exercício cadastrado com sucesso.';
            resetForm();
        }

        await loadExercises();
    } catch (err) {
        const parsed = extractError(err);
        error.value = parsed.message;
        Object.assign(fieldErrors, parsed.errors ?? {});
    } finally {
        saving.value = false;
    }
}

async function restoreExercise() {
    if (! selectedId.value) {
        return;
    }

    restoring.value = true;
    error.value = '';
    success.value = '';

    try {
        if (isTrashed.value) {
            await api.post(`/exercises/${selectedId.value}/restore`);
        } else {
            await api.put(`/exercises/${selectedId.value}`, { status: 'active' });
        }

        success.value = 'Exercício reativado com sucesso.';
        scopeFilter.value = 'active';
        await loadExercises();
        await loadActivityLogs(selectedId.value);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        restoring.value = false;
    }
}

async function removeExercise() {
    if (! selectedId.value || isTrashed.value) {
        return;
    }

    const exerciseName = form.name || 'este exercício';

    if (! window.confirm(`Excluir ${exerciseName}? O registro será removido logicamente.`)) {
        return;
    }

    deleting.value = true;
    error.value = '';
    success.value = '';

    try {
        await api.delete(`/exercises/${selectedId.value}`);
        success.value = 'Exercício excluído com sucesso.';
        scopeFilter.value = 'inactive';
        resetForm();
        await loadExercises();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        deleting.value = false;
    }
}

onMounted(async () => {
    try {
        await loadCatalogs();
        await loadExercises();
    } catch (err) {
        error.value = extractError(err).message;
        loading.value = false;
    }
});
</script>
