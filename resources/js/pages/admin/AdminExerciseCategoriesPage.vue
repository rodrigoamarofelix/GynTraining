<template>
    <AppLayout subtitle="Categorias de exercício">
        <div class="mb-6">
            <RouterLink to="/admin">
                <UiButton variant="ghost">← Admin</UiButton>
            </RouterLink>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-6">
                <UiCard :title="isEditing ? 'Editar categoria' : 'Nova categoria'">
                    <p v-if="! isEditing" class="mb-4 text-sm text-slate-400">
                        Catálogo global — usado em exercícios de todas as academias.
                    </p>
                    <p v-else-if="isTrashed" class="mb-4 text-sm text-amber-300">
                        Categoria excluída logicamente. Use reativar para restaurar.
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
                            placeholder="Composto"
                            :error="fieldErrors.name"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.description"
                            label="Descrição"
                            placeholder="Exercícios multiarticulares"
                            :error="fieldErrors.description"
                            :disabled="isTrashed"
                        />
                        <div class="flex flex-wrap gap-3">
                            <UiButton v-if="! isTrashed" type="submit" :loading="saving">
                                {{ isEditing ? 'Salvar alterações' : 'Cadastrar categoria' }}
                            </UiButton>
                            <UiButton
                                v-if="isEditing && isTrashed"
                                type="button"
                                :loading="restoring"
                                @click="restoreCategory"
                            >
                                Reativar categoria
                            </UiButton>
                            <UiButton v-if="isEditing" type="button" variant="secondary" @click="cancelEdit">
                                Cancelar
                            </UiButton>
                            <UiButton
                                v-if="isEditing && ! isTrashed"
                                type="button"
                                variant="danger"
                                :loading="deleting"
                                @click="removeCategory"
                            >
                                Excluir categoria
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
                                <UiBadge variant="info">{{ exerciseCategoryActivityActionLabel(log.action) }}</UiBadge>
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

            <UiCard title="Categorias cadastradas">
                <div class="mb-4 flex flex-wrap gap-2">
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
                <UiInput
                    v-model="search"
                    label="Buscar"
                    placeholder="Nome da categoria..."
                    @input="debouncedLoad"
                />
                <div v-if="loading" class="mt-4 text-sm text-slate-400">Carregando...</div>
                <div v-else-if="categories.length" class="mt-4 max-h-[32rem] space-y-3 overflow-y-auto">
                    <div
                        v-for="category in categories"
                        :key="category.id"
                        class="cursor-pointer rounded-xl border bg-slate-950/60 p-4 transition hover:border-slate-700"
                        :class="selectedId === category.id
                            ? 'border-emerald-500/60 ring-1 ring-emerald-500/30'
                            : 'border-slate-800'"
                        @click="selectCategory(category)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-white">{{ category.name }}</p>
                                <p class="text-sm text-slate-400">{{ category.description ?? '—' }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ category.exercises_count ?? 0 }} exercícios
                                </p>
                            </div>
                            <UiBadge :variant="category.deleted_at ? 'danger' : 'success'">
                                {{ category.deleted_at ? 'Excluída' : 'Ativa' }}
                            </UiBadge>
                        </div>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Nenhuma categoria encontrada.</p>
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
import { exerciseCategoryActivityActionLabel, formatDateTime } from '../../utils/format';

const categories = ref([]);
const activityLogs = ref([]);
const loading = ref(true);
const loadingActivity = ref(false);
const saving = ref(false);
const deleting = ref(false);
const restoring = ref(false);
const selectedId = ref(null);
const selectedCategory = ref(null);
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
const isTrashed = computed(() => Boolean(selectedCategory.value?.deleted_at));

function clearFieldErrors() {
    Object.keys(fieldErrors).forEach((key) => delete fieldErrors[key]);
}

function resetForm() {
    selectedId.value = null;
    selectedCategory.value = null;
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

async function loadActivityLogs(categoryId) {
    loadingActivity.value = true;

    try {
        const response = await api.get(`/exercise-categories/${categoryId}/activity-logs`, { params: { per_page: 30 } });
        activityLogs.value = extractData(response);
    } catch {
        activityLogs.value = [];
    } finally {
        loadingActivity.value = false;
    }
}

function selectCategory(category) {
    error.value = '';
    success.value = '';
    clearFieldErrors();
    selectedId.value = category.id;
    selectedCategory.value = category;
    form.name = category.name ?? '';
    form.description = category.description ?? '';
    loadActivityLogs(category.id);
}

function setScopeFilter(value) {
    scopeFilter.value = value;
    loadCategories();
}

async function loadCategories() {
    loading.value = true;

    try {
        const response = await api.get('/exercise-categories', {
            params: {
                search: search.value || undefined,
                scope: scopeFilter.value,
                per_page: 50,
            },
        });
        categories.value = extractData(response);

        if (selectedId.value) {
            const current = categories.value.find((category) => category.id === selectedId.value);

            if (current) {
                selectCategory(current);
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
    debounceTimer = setTimeout(loadCategories, 300);
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
            await api.put(`/exercise-categories/${selectedId.value}`, payload);
            success.value = 'Categoria atualizada com sucesso.';
            await loadActivityLogs(selectedId.value);
        } else {
            await api.post('/exercise-categories', payload);
            success.value = 'Categoria cadastrada com sucesso.';
            resetForm();
        }

        await loadCategories();
    } catch (err) {
        const parsed = extractError(err);
        error.value = parsed.message;
        Object.assign(fieldErrors, parsed.errors ?? {});
    } finally {
        saving.value = false;
    }
}

async function restoreCategory() {
    if (! selectedId.value) {
        return;
    }

    restoring.value = true;
    error.value = '';
    success.value = '';

    try {
        await api.post(`/exercise-categories/${selectedId.value}/restore`);
        success.value = 'Categoria reativada com sucesso.';
        scopeFilter.value = 'active';
        await loadCategories();
        await loadActivityLogs(selectedId.value);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        restoring.value = false;
    }
}

async function removeCategory() {
    if (! selectedId.value || isTrashed.value) {
        return;
    }

    const categoryName = form.name || 'esta categoria';

    if (! window.confirm(`Excluir ${categoryName}? O registro será removido logicamente.`)) {
        return;
    }

    deleting.value = true;
    error.value = '';
    success.value = '';

    try {
        await api.delete(`/exercise-categories/${selectedId.value}`);
        success.value = 'Categoria excluída com sucesso.';
        scopeFilter.value = 'inactive';
        resetForm();
        await loadCategories();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        deleting.value = false;
    }
}

onMounted(loadCategories);
</script>
