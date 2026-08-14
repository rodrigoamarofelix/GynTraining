<template>
    <AppLayout subtitle="Metas">
        <div class="grid gap-6 lg:grid-cols-2">
            <UiCard title="Nova meta">
                <form class="space-y-4" @submit.prevent="submit">
                    <UiAlert v-if="error" :message="error" />
                    <UiAlert v-if="success" :message="success" variant="success" />
                    <UiInput v-model="form.name" label="Nome" placeholder="Perder 5 kg" required />
                    <UiInput v-model="form.description" label="Descrição" />
                    <UiInput v-model="form.target" label="Meta" type="number" step="0.1" required />
                    <UiInput v-model="form.current_value" label="Valor atual" type="number" step="0.1" />
                    <UiInput v-model="form.unit" label="Unidade" placeholder="kg" />
                    <UiInput v-model="form.target_date" label="Data alvo" type="date" />
                    <UiButton type="submit" :loading="saving">Criar meta</UiButton>
                </form>
            </UiCard>

            <UiCard title="Metas ativas">
                <div v-if="loading" class="text-sm text-slate-400">Carregando...</div>
                <div v-else-if="goals.length" class="space-y-4">
                    <div
                        v-for="goal in goals"
                        :key="goal.id"
                        class="rounded-xl border border-slate-800 bg-slate-950/60 p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-medium text-white">{{ goal.name }}</p>
                            <UiBadge variant="success">{{ goal.progress_percentage ?? 0 }}%</UiBadge>
                        </div>
                        <p v-if="goal.description" class="mt-1 text-sm text-slate-400">{{ goal.description }}</p>
                        <p class="mt-2 text-xs text-slate-500">
                            {{ goal.current_value }} / {{ goal.target }} {{ goal.unit ?? '' }}
                        </p>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
                            <div
                                class="h-full rounded-full bg-emerald-500"
                                :style="{ width: `${goal.progress_percentage ?? 0}%` }"
                            />
                        </div>

                        <form class="mt-4 flex flex-wrap items-end gap-3" @submit.prevent="updateGoal(goal.id)">
                            <UiInput
                                v-model="progressForms[goal.id]"
                                label="Atualizar progresso"
                                type="number"
                                step="0.1"
                                class="min-w-[140px] flex-1"
                            />
                            <UiButton type="submit" size="sm" :loading="updatingId === goal.id">
                                Salvar
                            </UiButton>
                            <UiButton
                                type="button"
                                size="sm"
                                variant="secondary"
                                :loading="completingId === goal.id"
                                @click="completeGoal(goal.id)"
                            >
                                Concluir
                            </UiButton>
                        </form>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Nenhuma meta cadastrada.</p>
            </UiCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiInput from '../../components/ui/UiInput.vue';
import AppLayout from '../../layouts/AppLayout.vue';

const goals = ref([]);
const loading = ref(true);
const saving = ref(false);
const updatingId = ref(null);
const completingId = ref(null);
const error = ref('');
const success = ref('');
const progressForms = reactive({});

const form = reactive({
    name: '',
    description: '',
    target: '',
    current_value: '0',
    unit: 'kg',
    target_date: '',
});

function syncProgressForms() {
    goals.value.forEach((goal) => {
        progressForms[goal.id] = String(goal.current_value ?? 0);
    });
}

async function loadGoals() {
    loading.value = true;

    try {
        const response = await api.get('/goals', { params: { status: 'active', per_page: 20 } });
        goals.value = extractData(response);
        syncProgressForms();
    } finally {
        loading.value = false;
    }
}

async function submit() {
    saving.value = true;
    error.value = '';
    success.value = '';

    try {
        await api.post('/goals', {
            name: form.name,
            description: form.description || undefined,
            target: Number(form.target),
            current_value: Number(form.current_value),
            unit: form.unit,
            target_date: form.target_date || undefined,
        });

        form.name = '';
        form.description = '';
        form.target = '';
        form.current_value = '0';
        success.value = 'Meta criada com sucesso.';
        await loadGoals();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        saving.value = false;
    }
}

async function updateGoal(goalId) {
    updatingId.value = goalId;
    error.value = '';

    try {
        await api.put(`/goals/${goalId}`, {
            current_value: Number(progressForms[goalId]),
        });
        success.value = 'Progresso atualizado.';
        await loadGoals();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        updatingId.value = null;
    }
}

async function completeGoal(goalId) {
    completingId.value = goalId;
    error.value = '';

    try {
        await api.put(`/goals/${goalId}`, { status: 'completed' });
        success.value = 'Meta concluída.';
        await loadGoals();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        completingId.value = null;
    }
}

onMounted(loadGoals);
</script>
