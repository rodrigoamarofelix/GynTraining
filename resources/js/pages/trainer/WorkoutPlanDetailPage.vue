<template>
    <AppLayout subtitle="Ficha de treino">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <RouterLink v-if="plan?.student_id" :to="{ name: 'trainer.student', params: { id: plan.student_id } }">
                <UiButton variant="ghost">← Aluno</UiButton>
            </RouterLink>
            <div v-if="plan" class="flex flex-wrap gap-2">
                <template v-if="! plan.deleted_at">
                    <RouterLink :to="{ name: 'trainer.workout.edit', params: { id: plan.id } }">
                        <UiButton variant="secondary">Editar exercícios</UiButton>
                    </RouterLink>
                    <UiButton variant="danger" :loading="deleting" @click="removePlan">
                        Excluir ficha (lógico)
                    </UiButton>
                </template>
                <UiButton v-else :loading="restoring" @click="restorePlan">
                    Reativar ficha
                </UiButton>
            </div>
        </div>

        <div v-if="loading" class="py-20 text-center text-slate-400">Carregando ficha...</div>
        <UiAlert v-else-if="error" :message="error" />

        <div v-else-if="plan" class="space-y-6">
            <UiCard :title="plan.name" :subtitle="plan.description">
                <UiBadge v-if="plan.deleted_at" variant="danger" class="mb-4">Excluída logicamente</UiBadge>
                <form v-if="! plan.deleted_at" class="grid gap-4 md:grid-cols-2" @submit.prevent="save">
                    <UiInput v-model="editForm.name" label="Nome" />
                    <UiSelect
                        v-model="editForm.status"
                        label="Status"
                        :options="statusOptions"
                    />
                    <UiInput
                        v-model="editForm.description"
                        label="Descrição"
                        class="md:col-span-2"
                    />
                    <div class="md:col-span-2">
                        <UiButton type="submit" :loading="saving">Atualizar dados</UiButton>
                    </div>
                </form>
                <UiAlert v-if="saveMessage" :message="saveMessage" variant="success" class="mt-4" />
            </UiCard>

            <div
                v-for="day in plan.days"
                :key="day.id"
                class="space-y-4"
            >
                <UiCard :title="day.name" :subtitle="day.description" eyebrow="Dia">
                    <div
                        v-for="workoutExercise in day.exercises"
                        :key="workoutExercise.id"
                        class="mb-4 rounded-xl border border-slate-800 bg-slate-950/60 p-4"
                    >
                        <p class="font-medium text-white">{{ workoutExercise.exercise?.name }}</p>
                        <p class="mt-1 text-sm text-slate-400">
                            Descanso: {{ workoutExercise.rest_time ?? 60 }}s
                        </p>
                        <div class="mt-3 space-y-1">
                            <div
                                v-for="set in workoutExercise.sets"
                                :key="set.id"
                                class="flex justify-between text-sm text-slate-300"
                            >
                                <span>Série {{ set.set_number }}</span>
                                <span>{{ set.repetitions }} reps · {{ set.load ?? 0 }} kg</span>
                            </div>
                        </div>
                    </div>
                </UiCard>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiInput from '../../components/ui/UiInput.vue';
import UiSelect from '../../components/ui/UiSelect.vue';
import AppLayout from '../../layouts/AppLayout.vue';

const route = useRoute();
const router = useRouter();
const plan = ref(null);
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const restoring = ref(false);
const error = ref('');
const saveMessage = ref('');

const editForm = reactive({
    name: '',
    description: '',
    status: 'active',
});

const statusOptions = [
    { value: 'draft', label: 'Rascunho' },
    { value: 'active', label: 'Ativa' },
    { value: 'inactive', label: 'Inativa' },
    { value: 'completed', label: 'Concluída' },
];

async function loadPlan() {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get(`/workouts/${route.params.id}`);
        plan.value = extractData(response);
        editForm.name = plan.value.name;
        editForm.description = plan.value.description ?? '';
        editForm.status = plan.value.status ?? 'active';
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loading.value = false;
    }
}

async function save() {
    saving.value = true;
    saveMessage.value = '';

    try {
        const response = await api.put(`/workouts/${route.params.id}`, {
            name: editForm.name,
            description: editForm.description || null,
            status: editForm.status,
        });

        plan.value = extractData(response);
        saveMessage.value = 'Dados atualizados. O aluno será notificado se houver mudanças.';
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        saving.value = false;
    }
}

async function restorePlan() {
    restoring.value = true;
    error.value = '';

    try {
        const response = await api.post(`/workouts/${route.params.id}/restore`);
        plan.value = extractData(response);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        restoring.value = false;
    }
}

async function removePlan() {
    const planName = plan.value?.name || 'esta ficha';

    if (! window.confirm(`Excluir ${planName}? O registro será removido logicamente e poderá ser reativado.`)) {
        return;
    }

    deleting.value = true;
    error.value = '';

    try {
        await api.delete(`/workouts/${route.params.id}`);
        router.push({
            name: 'trainer.student',
            params: { id: plan.value.student_id },
            query: { scope: 'inactive' },
        });
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        deleting.value = false;
    }
}

onMounted(loadPlan);
</script>
