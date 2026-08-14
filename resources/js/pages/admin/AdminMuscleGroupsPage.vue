<template>
    <AppLayout subtitle="Grupos musculares">
        <div class="mb-6">
            <RouterLink to="/admin">
                <UiButton variant="ghost">← Admin</UiButton>
            </RouterLink>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-6">
                <UiCard :title="isEditing ? 'Editar grupo' : 'Novo grupo'">
                    <p v-if="! isEditing" class="mb-4 text-sm text-slate-400">
                        Catálogo global — usado em exercícios de todas as academias.
                    </p>
                    <p v-else-if="isTrashed" class="mb-4 text-sm text-amber-300">
                        Grupo excluído logicamente. Use reativar para restaurar.
                    </p>
                    <p v-else class="mb-4 text-sm text-slate-400">
                        Altere os dados e clique em salvar.
                    </p>
                    <form class="space-y-4" @submit.prevent="submit">
                        <UiAlert v-if="error" :message="error" />
                        <UiAlert v-if="success" :message="success" variant="success" />
                        <UiInput
                            v-model="form.name"
                            label="Nome"
                            placeholder="Peito"
                            :error="fieldErrors.name"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.description"
                            label="Descrição"
                            placeholder="Músculos do tórax"
                            :error="fieldErrors.description"
                            :disabled="isTrashed"
                        />
                        <div class="flex flex-wrap gap-3">
                            <UiButton v-if="! isTrashed" type="submit" :loading="saving">
                                {{ isEditing ? 'Salvar alterações' : 'Cadastrar grupo' }}
                            </UiButton>
                            <UiButton
                                v-if="isEditing && isTrashed"
                                type="button"
                                :loading="restoring"
                                @click="restoreGroup"
                            >
                                Reativar grupo
                            </UiButton>
                            <UiButton v-if="isEditing" type="button" variant="secondary" @click="cancelEdit">
                                Cancelar
                            </UiButton>
                            <UiButton
                                v-if="isEditing && ! isTrashed"
                                type="button"
                                variant="danger"
                                :loading="deleting"
                                @click="removeGroup"
                            >
                                Excluir grupo
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
                                <UiBadge variant="info">{{ muscleGroupActivityActionLabel(log.action) }}</UiBadge>
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

            <UiCard title="Grupos cadastrados">
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
                        Excluídos
                    </UiButton>
                </div>
                <UiInput
                    v-model="search"
                    label="Buscar"
                    placeholder="Nome do grupo..."
                    @input="debouncedLoad"
                />
                <div v-if="loading" class="mt-4 text-sm text-slate-400">Carregando...</div>
                <div v-else-if="groups.length" class="mt-4 max-h-[32rem] space-y-3 overflow-y-auto">
                    <div
                        v-for="group in groups"
                        :key="group.id"
                        class="cursor-pointer rounded-xl border bg-slate-950/60 p-4 transition hover:border-slate-700"
                        :class="selectedId === group.id
                            ? 'border-emerald-500/60 ring-1 ring-emerald-500/30'
                            : 'border-slate-800'"
                        @click="selectGroup(group)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-white">{{ group.name }}</p>
                                <p class="text-sm text-slate-400">{{ group.description ?? '—' }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ group.exercises_count ?? 0 }} exercícios
                                </p>
                            </div>
                            <UiBadge :variant="group.deleted_at ? 'danger' : 'success'">
                                {{ group.deleted_at ? 'Excluído' : 'Ativo' }}
                            </UiBadge>
                        </div>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Nenhum grupo encontrado.</p>
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
import AppLayout from '../../layouts/AppLayout.vue';
import { formatDateTime, muscleGroupActivityActionLabel } from '../../utils/format';

const groups = ref([]);
const activityLogs = ref([]);
const loading = ref(true);
const loadingActivity = ref(false);
const saving = ref(false);
const deleting = ref(false);
const restoring = ref(false);
const selectedId = ref(null);
const selectedGroup = ref(null);
const error = ref('');
const success = ref('');
const search = ref('');
const scopeFilter = ref('active');
const fieldErrors = reactive({});
let debounceTimer = null;

const form = reactive({
    name: '',
    description: '',
});

const isEditing = computed(() => selectedId.value !== null);
const isTrashed = computed(() => Boolean(selectedGroup.value?.deleted_at));

function clearFieldErrors() {
    Object.keys(fieldErrors).forEach((key) => delete fieldErrors[key]);
}

function resetForm() {
    selectedId.value = null;
    selectedGroup.value = null;
    activityLogs.value = [];
    form.name = '';
    form.description = '';
}

function cancelEdit() {
    error.value = '';
    success.value = '';
    clearFieldErrors();
    resetForm();
}

async function loadActivityLogs(groupId) {
    loadingActivity.value = true;

    try {
        const response = await api.get(`/muscle-groups/${groupId}/activity-logs`, { params: { per_page: 30 } });
        activityLogs.value = extractData(response);
    } catch {
        activityLogs.value = [];
    } finally {
        loadingActivity.value = false;
    }
}

function selectGroup(group) {
    error.value = '';
    success.value = '';
    clearFieldErrors();
    selectedId.value = group.id;
    selectedGroup.value = group;
    form.name = group.name ?? '';
    form.description = group.description ?? '';
    loadActivityLogs(group.id);
}

function setScopeFilter(value) {
    scopeFilter.value = value;
    loadGroups();
}

async function loadGroups() {
    loading.value = true;

    try {
        const response = await api.get('/muscle-groups', {
            params: {
                search: search.value || undefined,
                scope: scopeFilter.value,
                per_page: 50,
            },
        });
        groups.value = extractData(response);

        if (selectedId.value) {
            const current = groups.value.find((group) => group.id === selectedId.value);

            if (current) {
                selectGroup(current);
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
    debounceTimer = setTimeout(loadGroups, 300);
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
        const payload = {
            name: form.name,
            description: form.description || null,
        };

        if (isEditing.value) {
            await api.put(`/muscle-groups/${selectedId.value}`, payload);
            success.value = 'Grupo atualizado com sucesso.';
            await loadActivityLogs(selectedId.value);
        } else {
            await api.post('/muscle-groups', payload);
            success.value = 'Grupo cadastrado com sucesso.';
            resetForm();
        }

        await loadGroups();
    } catch (err) {
        const parsed = extractError(err);
        error.value = parsed.message;
        Object.assign(fieldErrors, parsed.errors ?? {});
    } finally {
        saving.value = false;
    }
}

async function restoreGroup() {
    if (! selectedId.value) {
        return;
    }

    restoring.value = true;
    error.value = '';
    success.value = '';

    try {
        await api.post(`/muscle-groups/${selectedId.value}/restore`);
        success.value = 'Grupo reativado com sucesso.';
        scopeFilter.value = 'active';
        await loadGroups();
        await loadActivityLogs(selectedId.value);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        restoring.value = false;
    }
}

async function removeGroup() {
    if (! selectedId.value || isTrashed.value) {
        return;
    }

    const groupName = form.name || 'este grupo';

    if (! window.confirm(`Excluir ${groupName}? O registro será removido logicamente.`)) {
        return;
    }

    deleting.value = true;
    error.value = '';
    success.value = '';

    try {
        await api.delete(`/muscle-groups/${selectedId.value}`);
        success.value = 'Grupo excluído com sucesso.';
        scopeFilter.value = 'inactive';
        resetForm();
        await loadGroups();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        deleting.value = false;
    }
}

onMounted(loadGroups);
</script>
