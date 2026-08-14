<template>
    <AppLayout subtitle="Cadastrar professores">
        <div class="mb-6">
            <RouterLink to="/admin">
                <UiButton variant="ghost">← Admin</UiButton>
            </RouterLink>
        </div>

        <UiAlert
            v-if="routeGymLabel"
            class="mb-4"
            variant="info"
            :message="`Filtrando por academia: ${routeGymLabel}`"
        />

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-6">
                <UiCard :title="isEditing ? 'Editar professor' : 'Novo professor'">
                    <p v-if="! isEditing" class="mb-4 text-sm text-slate-400">
                        Cadastro interno pelo admin — entra como ativo.
                    </p>
                    <p v-else-if="isTrashed" class="mb-4 text-sm text-amber-300">
                        Professor excluído logicamente. Use reativar para restaurar o acesso.
                    </p>
                    <p v-else class="mb-4 text-sm text-slate-400">
                        Altere os dados e clique em salvar. Senha só se quiser trocar.
                    </p>
                    <form class="space-y-4" @submit.prevent="submit">
                        <UiAlert v-if="error" :message="error" />
                        <UiAlert v-if="success" :message="success" variant="success" />
                        <UiSelect
                            v-model="form.gym_id"
                            label="Academia"
                            placeholder="Selecione"
                            :options="gymOptions"
                            :error="fieldErrors.gym_id"
                            :disabled="isTrashed"
                        />
                        <UiSelect
                            v-if="isEditing && ! isTrashed"
                            v-model="form.status"
                            label="Status"
                            :options="statusOptions"
                            :error="fieldErrors.status"
                        />
                        <UiInput
                            v-model="form.name"
                            label="Nome"
                            placeholder="Carlos Personal"
                            :error="fieldErrors.name"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.email"
                            label="E-mail"
                            type="email"
                            placeholder="carlos@email.com"
                            :error="fieldErrors.email"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.phone"
                            label="Telefone (opcional)"
                            placeholder="(11) 99999-9999"
                            :error="fieldErrors.phone"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.password"
                            :label="isEditing ? 'Nova senha (opcional)' : 'Senha'"
                            type="password"
                            placeholder="••••••••"
                            :error="fieldErrors.password"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-if="(! isEditing || form.password) && ! isTrashed"
                            v-model="form.password_confirmation"
                            :label="isEditing ? 'Confirmar nova senha' : 'Confirmar senha'"
                            type="password"
                            placeholder="••••••••"
                        />
                        <UiInput
                            v-model="form.specialty"
                            label="Especialidade (opcional)"
                            placeholder="Hipertrofia, funcional..."
                            :error="fieldErrors.specialty"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.bio"
                            label="Bio (opcional)"
                            placeholder="Breve apresentação"
                            :error="fieldErrors.bio"
                            :disabled="isTrashed"
                        />
                        <div class="flex flex-wrap gap-3">
                            <UiButton v-if="! isTrashed" type="submit" :loading="saving">
                                {{ isEditing ? 'Salvar alterações' : 'Cadastrar professor' }}
                            </UiButton>
                            <UiButton
                                v-if="isEditing && (isTrashed || form.status === 'inactive')"
                                type="button"
                                :loading="restoring"
                                @click="restoreTrainer"
                            >
                                Reativar professor
                            </UiButton>
                            <UiButton v-if="isEditing" type="button" variant="secondary" @click="cancelEdit">
                                Cancelar
                            </UiButton>
                            <UiButton
                                v-if="isEditing && ! isTrashed"
                                type="button"
                                variant="danger"
                                :loading="deleting"
                                @click="removeTrainer"
                            >
                                Excluir professor
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
                                <UiBadge variant="info">{{ trainerActivityActionLabel(log.action) }}</UiBadge>
                                <span class="text-xs text-slate-500">{{ formatDateTime(log.created_at) }}</span>
                            </div>
                            <p class="mt-2 text-slate-300">{{ log.summary }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Por: {{ log.performer?.name ?? 'Sistema' }}
                            </p>
                            <ul v-if="log.changes?.length" class="mt-2 space-y-1 text-xs text-slate-400">
                                <li v-for="change in log.changes" :key="`${log.id}-${change.field}`">
                                    <span class="text-slate-300">{{ change.label }}:</span>
                                    {{ change.old ?? '—' }} → {{ change.new ?? '—' }}
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400">Nenhuma alteração registrada.</p>
                </UiCard>
            </div>

            <UiCard title="Professores cadastrados">
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
                    placeholder="Nome ou e-mail..."
                    @input="debouncedLoad"
                />
                <div v-if="loading" class="mt-4 text-sm text-slate-400">Carregando...</div>
                <div v-else-if="trainers.length" class="mt-4 max-h-[32rem] space-y-3 overflow-y-auto">
                    <div
                        v-for="trainer in trainers"
                        :key="trainer.id"
                        class="cursor-pointer rounded-xl border bg-slate-950/60 p-4 transition hover:border-slate-700"
                        :class="selectedId === trainer.id
                            ? 'border-emerald-500/60 ring-1 ring-emerald-500/30'
                            : 'border-slate-800'"
                        @click="selectTrainer(trainer)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-white">{{ trainer.user?.name ?? '—' }}</p>
                                <p class="text-sm text-slate-400">{{ trainer.user?.email ?? '—' }}</p>
                                <p v-if="trainer.specialty" class="mt-1 text-xs text-emerald-400/80">
                                    {{ trainer.specialty }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">{{ trainer.gym?.name ?? '—' }}</p>
                            </div>
                            <UiBadge :variant="trainerBadgeVariant(trainer)">
                                {{ trainerBadgeLabel(trainer) }}
                            </UiBadge>
                        </div>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Nenhum professor encontrado.</p>
            </UiCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiInput from '../../components/ui/UiInput.vue';
import UiSelect from '../../components/ui/UiSelect.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import {
    formatDateTime,
    profileStatusLabel,
    trainerActivityActionLabel,
} from '../../utils/format';

const route = useRoute();

const trainers = ref([]);
const gyms = ref([]);
const activityLogs = ref([]);
const loading = ref(true);
const loadingActivity = ref(false);
const saving = ref(false);
const deleting = ref(false);
const restoring = ref(false);
const selectedId = ref(null);
const selectedTrainer = ref(null);
const error = ref('');
const success = ref('');
const search = ref('');
const scopeFilter = ref('active');
const fieldErrors = reactive({});
let debounceTimer = null;

const form = reactive({
    gym_id: '',
    status: 'active',
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    specialty: '',
    bio: '',
});

const gymOptions = computed(() => gyms.value.map((gym) => ({
    value: String(gym.id),
    label: gym.name,
})));

const isEditing = computed(() => selectedId.value !== null);
const isTrashed = computed(() => Boolean(selectedTrainer.value?.deleted_at));

const statusOptions = [
    { value: 'active', label: 'Ativo' },
    { value: 'inactive', label: 'Inativo' },
];

const routeGymId = computed(() => {
    const gymId = Number(route.query.gym_id);

    return Number.isFinite(gymId) && gymId > 0 ? gymId : null;
});

const routeMemberId = computed(() => {
    const memberId = Number(route.query.id);

    return Number.isFinite(memberId) && memberId > 0 ? memberId : null;
});

const routeGymLabel = computed(() => {
    if (route.query.gym_name) {
        return String(route.query.gym_name);
    }

    if (! routeGymId.value) {
        return '';
    }

    return gyms.value.find((gym) => gym.id === routeGymId.value)?.name ?? `ID ${routeGymId.value}`;
});

function trainerBadgeLabel(trainer) {
    if (trainer.deleted_at) {
        return 'Excluído';
    }

    return profileStatusLabel(trainer.status);
}

function trainerBadgeVariant(trainer) {
    if (trainer.deleted_at) {
        return 'danger';
    }

    if (trainer.status === 'active') {
        return 'success';
    }

    return 'default';
}

function clearFieldErrors() {
    Object.keys(fieldErrors).forEach((key) => delete fieldErrors[key]);
}

function resetForm() {
    selectedId.value = null;
    selectedTrainer.value = null;
    activityLogs.value = [];
    form.name = '';
    form.email = '';
    form.phone = '';
    form.password = '';
    form.password_confirmation = '';
    form.specialty = '';
    form.bio = '';
    form.status = 'active';

    if (gyms.value.length !== 1) {
        form.gym_id = '';
    }
}

function cancelEdit() {
    error.value = '';
    success.value = '';
    clearFieldErrors();
    resetForm();
}

async function loadActivityLogs(trainerId) {
    loadingActivity.value = true;

    try {
        const response = await api.get(`/trainers/${trainerId}/activity-logs`, { params: { per_page: 30 } });
        activityLogs.value = extractData(response);
    } catch {
        activityLogs.value = [];
    } finally {
        loadingActivity.value = false;
    }
}

function selectTrainer(trainer) {
    error.value = '';
    success.value = '';
    clearFieldErrors();
    selectedId.value = trainer.id;
    selectedTrainer.value = trainer;
    form.gym_id = String(trainer.gym_id ?? '');
    form.status = trainer.deleted_at ? 'inactive' : (trainer.status ?? 'active');
    form.name = trainer.user?.name ?? '';
    form.email = trainer.user?.email ?? '';
    form.phone = trainer.user?.phone ?? '';
    form.password = '';
    form.password_confirmation = '';
    form.specialty = trainer.specialty ?? '';
    form.bio = trainer.bio ?? '';
    loadActivityLogs(trainer.id);
}

function setScopeFilter(value) {
    scopeFilter.value = value;
    loadTrainers();
}

async function loadGyms() {
    const response = await api.get('/gyms', { params: { per_page: 50, status: 'active' } });
    gyms.value = extractData(response);

    if (gyms.value.length === 1) {
        form.gym_id = String(gyms.value[0].id);
    }
}

async function loadTrainers() {
    loading.value = true;

    try {
        const response = await api.get('/trainers', {
            params: {
                search: search.value || undefined,
                scope: scopeFilter.value,
                gym_id: routeGymId.value || undefined,
                per_page: 50,
            },
        });
        trainers.value = extractData(response);

        if (routeMemberId.value) {
            const fromRoute = trainers.value.find((trainer) => trainer.id === routeMemberId.value);

            if (fromRoute) {
                selectTrainer(fromRoute);
                return;
            }
        }

        if (selectedId.value) {
            const current = trainers.value.find((trainer) => trainer.id === selectedId.value);

            if (current) {
                selectTrainer(current);
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
    debounceTimer = setTimeout(loadTrainers, 300);
}

function buildPayload() {
    const payload = {
        gym_id: Number(form.gym_id),
        name: form.name,
        email: form.email,
        phone: form.phone || null,
        specialty: form.specialty || null,
        bio: form.bio || null,
    };

    if (form.password) {
        payload.password = form.password;
    }

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

    if (form.password && form.password !== form.password_confirmation) {
        error.value = 'As senhas não conferem.';
        saving.value = false;
        return;
    }

    if (! isEditing.value && ! form.password) {
        error.value = 'Informe a senha do professor.';
        saving.value = false;
        return;
    }

    try {
        const payload = buildPayload();

        if (isEditing.value) {
            await api.put(`/trainers/${selectedId.value}`, payload);
            success.value = 'Professor atualizado com sucesso.';
            await loadActivityLogs(selectedId.value);
        } else {
            await api.post('/trainers', payload);
            success.value = 'Professor cadastrado com sucesso.';
            resetForm();
        }

        await loadTrainers();
    } catch (err) {
        const parsed = extractError(err);
        error.value = parsed.message;
        Object.assign(fieldErrors, parsed.errors ?? {});
    } finally {
        saving.value = false;
    }
}

async function restoreTrainer() {
    if (! selectedId.value) {
        return;
    }

    restoring.value = true;
    error.value = '';
    success.value = '';

    try {
        if (isTrashed.value) {
            await api.post(`/trainers/${selectedId.value}/restore`);
        } else {
            await api.put(`/trainers/${selectedId.value}`, { status: 'active' });
        }

        success.value = 'Professor reativado com sucesso.';
        scopeFilter.value = 'active';
        await loadTrainers();
        await loadActivityLogs(selectedId.value);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        restoring.value = false;
    }
}

async function removeTrainer() {
    if (! selectedId.value || isTrashed.value) {
        return;
    }

    const trainerName = form.name || 'este professor';

    if (! window.confirm(`Excluir ${trainerName}? O registro será removido logicamente.`)) {
        return;
    }

    deleting.value = true;
    error.value = '';
    success.value = '';

    try {
        await api.delete(`/trainers/${selectedId.value}`);
        success.value = 'Professor excluído com sucesso.';
        scopeFilter.value = 'inactive';
        resetForm();
        await loadTrainers();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        deleting.value = false;
    }
}

function applyRouteFilters() {
    if (route.query.scope && ['active', 'inactive'].includes(route.query.scope)) {
        scopeFilter.value = route.query.scope;
    }
}

watch(
    () => [route.query.gym_id, route.query.scope, route.query.id],
    async () => {
        applyRouteFilters();
        await loadTrainers();
    },
);

onMounted(async () => {
    applyRouteFilters();

    try {
        await Promise.all([loadGyms(), loadTrainers()]);
    } catch (err) {
        error.value = extractError(err).message;
        loading.value = false;
    }
});
</script>
