<template>
    <AppLayout subtitle="Cadastrar alunos">
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
                <UiCard :title="isEditing ? 'Editar aluno' : 'Novo aluno'">
                    <p v-if="! isEditing" class="mb-4 text-sm text-slate-400">
                        Cadastro direto pelo admin já entra como ativo.
                    </p>
                    <p v-else-if="isTrashed" class="mb-4 text-sm text-amber-300">
                        Aluno excluído logicamente. Use reativar para restaurar o acesso.
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
                            v-model="form.trainer_id"
                            label="Professor (opcional)"
                            placeholder="Selecione"
                            :options="trainerOptionsForGym(form.gym_id)"
                            :error="fieldErrors.trainer_id"
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
                            placeholder="Maria Aluna"
                            :error="fieldErrors.name"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.email"
                            label="E-mail"
                            type="email"
                            placeholder="maria@email.com"
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
                            v-model="form.birth_date"
                            label="Data de nascimento (opcional)"
                            type="date"
                            :error="fieldErrors.birth_date"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.notes"
                            label="Observações (opcional)"
                            placeholder="Restrições, objetivos..."
                            :error="fieldErrors.notes"
                            :disabled="isTrashed"
                        />
                        <div class="flex flex-wrap gap-3">
                            <UiButton v-if="! isTrashed" type="submit" :loading="saving">
                                {{ isEditing ? 'Salvar alterações' : 'Cadastrar aluno' }}
                            </UiButton>
                            <UiButton
                                v-if="isEditing && (isTrashed || form.status === 'inactive')"
                                type="button"
                                :loading="restoring"
                                @click="restoreStudent"
                            >
                                Reativar aluno
                            </UiButton>
                            <UiButton v-if="isEditing" type="button" variant="secondary" @click="cancelEdit">
                                Cancelar
                            </UiButton>
                            <UiButton
                                v-if="isEditing && ! isTrashed"
                                type="button"
                                variant="danger"
                                :loading="deleting"
                                @click="removeStudent"
                            >
                                Excluir aluno
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
                                <UiBadge variant="info">{{ studentActivityActionLabel(log.action) }}</UiBadge>
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

            <UiCard title="Alunos cadastrados">
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
                        :variant="scopeFilter === 'pending' ? 'primary' : 'secondary'"
                        @click="setScopeFilter('pending')"
                    >
                        Pendentes
                        <span v-if="pendingTotal" class="ml-1 rounded-full bg-amber-500/20 px-2 py-0.5 text-xs">
                            {{ pendingTotal }}
                        </span>
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
                <div v-else-if="students.length" class="mt-4 max-h-[32rem] space-y-3 overflow-y-auto">
                    <div
                        v-for="student in students"
                        :key="student.id"
                        class="cursor-pointer rounded-xl border bg-slate-950/60 p-4 transition hover:border-slate-700"
                        :class="selectedId === student.id
                            ? 'border-emerald-500/60 ring-1 ring-emerald-500/30'
                            : 'border-slate-800'"
                        @click="selectStudent(student)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-white">{{ student.user?.name ?? '—' }}</p>
                                <p class="text-sm text-slate-400">{{ student.user?.email ?? '—' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ student.gym?.name ?? '—' }}</p>
                            </div>
                            <UiBadge :variant="studentBadgeVariant(student)">
                                {{ studentBadgeLabel(student) }}
                            </UiBadge>
                        </div>

                        <p v-if="student.trainer?.user?.name" class="mt-2 text-xs text-emerald-400/80">
                            Professor: {{ student.trainer.user.name }}
                        </p>

                        <div
                            v-if="student.status === 'pending' && ! student.deleted_at"
                            class="mt-4 space-y-3 rounded-lg border border-amber-500/20 bg-amber-500/5 p-3"
                            @click.stop
                        >
                            <p class="text-sm text-amber-200">Aprovar cadastro e definir professor</p>
                            <UiSelect
                                v-model="approvalSelections[student.id]"
                                label="Professor responsável"
                                placeholder="Selecione"
                                :options="trainerOptionsForGym(student.gym_id)"
                                :error="approvalErrors[student.id]"
                            />
                            <UiButton
                                size="sm"
                                :loading="approvingId === student.id"
                                @click="approveStudent(student)"
                            >
                                Aprovar aluno
                            </UiButton>
                        </div>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Nenhum aluno encontrado.</p>
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
import { useNotificationsStore } from '../../stores/notifications';
import {
    firstValidationError,
    formatDateTime,
    profileStatusLabel,
    studentActivityActionLabel,
} from '../../utils/format';

const route = useRoute();
const notificationsStore = useNotificationsStore();

const students = ref([]);
const gyms = ref([]);
const trainers = ref([]);
const activityLogs = ref([]);
const loading = ref(true);
const loadingActivity = ref(false);
const saving = ref(false);
const deleting = ref(false);
const restoring = ref(false);
const approvingId = ref(null);
const selectedId = ref(null);
const selectedStudent = ref(null);
const error = ref('');
const success = ref('');
const search = ref('');
const scopeFilter = ref('active');
const fieldErrors = reactive({});
const approvalSelections = reactive({});
const approvalErrors = reactive({});
let debounceTimer = null;

const form = reactive({
    gym_id: '',
    trainer_id: '',
    status: 'active',
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    birth_date: '',
    notes: '',
});

const gymOptions = computed(() => gyms.value.map((gym) => ({
    value: String(gym.id),
    label: gym.name,
})));

const isEditing = computed(() => selectedId.value !== null);
const isTrashed = computed(() => Boolean(selectedStudent.value?.deleted_at));

const statusOptions = [
    { value: 'active', label: 'Ativo' },
    { value: 'pending', label: 'Pendente' },
    { value: 'inactive', label: 'Inativo' },
];

const pendingTotal = ref(0);

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

function trainerOptionsForGym(gymId) {
    const normalizedGymId = Number(gymId);

    return trainers.value
        .filter((trainer) => trainer.gym_id === normalizedGymId)
        .map((trainer) => ({
            value: String(trainer.id),
            label: trainer.user?.name ?? 'Professor',
        }));
}

function studentBadgeLabel(student) {
    if (student.deleted_at) {
        return 'Excluído';
    }

    return profileStatusLabel(student.status);
}

function studentBadgeVariant(student) {
    if (student.deleted_at) {
        return 'danger';
    }

    if (student.status === 'pending') {
        return 'warning';
    }

    if (student.status === 'active') {
        return 'success';
    }

    return 'default';
}

function clearFieldErrors() {
    Object.keys(fieldErrors).forEach((key) => delete fieldErrors[key]);
}

function resetForm() {
    selectedId.value = null;
    selectedStudent.value = null;
    activityLogs.value = [];
    form.name = '';
    form.email = '';
    form.phone = '';
    form.password = '';
    form.password_confirmation = '';
    form.birth_date = '';
    form.notes = '';
    form.trainer_id = '';
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

async function loadActivityLogs(studentId) {
    loadingActivity.value = true;

    try {
        const response = await api.get(`/students/${studentId}/activity-logs`, { params: { per_page: 30 } });
        activityLogs.value = extractData(response);
    } catch {
        activityLogs.value = [];
    } finally {
        loadingActivity.value = false;
    }
}

function selectStudent(student) {
    error.value = '';
    success.value = '';
    clearFieldErrors();
    selectedId.value = student.id;
    selectedStudent.value = student;
    form.gym_id = String(student.gym_id ?? '');
    form.trainer_id = student.trainer_id ? String(student.trainer_id) : '';
    form.status = student.deleted_at ? 'inactive' : (student.status ?? 'active');
    form.name = student.user?.name ?? '';
    form.email = student.user?.email ?? '';
    form.phone = student.user?.phone ?? '';
    form.password = '';
    form.password_confirmation = '';
    form.birth_date = student.birth_date ?? '';
    form.notes = student.notes ?? '';
    loadActivityLogs(student.id);
}

function setScopeFilter(value) {
    scopeFilter.value = value;
    loadStudents();
}

async function loadGyms() {
    const response = await api.get('/gyms', { params: { per_page: 50, status: 'active' } });
    gyms.value = extractData(response);

    if (gyms.value.length === 1) {
        form.gym_id = String(gyms.value[0].id);
    }
}

async function loadTrainers() {
    const response = await api.get('/trainers', { params: { per_page: 100, status: 'active' } });
    trainers.value = extractData(response);
}

async function loadPendingTotal() {
    const response = await api.get('/students', { params: { scope: 'pending', per_page: 50 } });
    pendingTotal.value = extractData(response).length;
}

async function loadStudents() {
    loading.value = true;

    try {
        const response = await api.get('/students', {
            params: {
                search: search.value || undefined,
                scope: scopeFilter.value,
                gym_id: routeGymId.value || undefined,
                per_page: 50,
            },
        });
        students.value = extractData(response);
        await loadPendingTotal();

        if (routeMemberId.value) {
            const fromRoute = students.value.find((student) => student.id === routeMemberId.value);

            if (fromRoute) {
                selectStudent(fromRoute);
                return;
            }
        }

        if (selectedId.value) {
            const current = students.value.find((student) => student.id === selectedId.value);

            if (current) {
                selectStudent(current);
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
    debounceTimer = setTimeout(loadStudents, 300);
}

function buildPayload() {
    const payload = {
        gym_id: Number(form.gym_id),
        trainer_id: form.trainer_id ? Number(form.trainer_id) : null,
        name: form.name,
        email: form.email,
        phone: form.phone || null,
        birth_date: form.birth_date || null,
        notes: form.notes || null,
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
        error.value = 'Informe a senha do aluno.';
        saving.value = false;
        return;
    }

    try {
        const payload = buildPayload();

        if (isEditing.value) {
            await api.put(`/students/${selectedId.value}`, payload);
            success.value = 'Aluno atualizado com sucesso.';
            await loadActivityLogs(selectedId.value);
        } else {
            await api.post('/students', payload);
            success.value = 'Aluno cadastrado com sucesso.';
            resetForm();
        }

        await loadStudents();

        if (isEditing.value && form.status === 'active') {
            await notificationsStore.fetchUnreadCount();
        }
    } catch (err) {
        const parsed = extractError(err);
        error.value = parsed.message;
        Object.assign(fieldErrors, parsed.errors ?? {});
    } finally {
        saving.value = false;
    }
}

async function restoreStudent() {
    if (! selectedId.value) {
        return;
    }

    restoring.value = true;
    error.value = '';
    success.value = '';

    try {
        if (isTrashed.value) {
            await api.post(`/students/${selectedId.value}/restore`);
        } else {
            await api.put(`/students/${selectedId.value}`, { status: 'active' });
        }

        success.value = 'Aluno reativado com sucesso.';
        scopeFilter.value = 'active';
        await loadStudents();
        await loadActivityLogs(selectedId.value);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        restoring.value = false;
    }
}

async function removeStudent() {
    if (! selectedId.value || isTrashed.value) {
        return;
    }

    const studentName = form.name || 'este aluno';

    if (! window.confirm(`Excluir ${studentName}? O registro será removido logicamente.`)) {
        return;
    }

    deleting.value = true;
    error.value = '';
    success.value = '';

    try {
        await api.delete(`/students/${selectedId.value}`);
        success.value = 'Aluno excluído com sucesso.';
        scopeFilter.value = 'inactive';
        resetForm();
        await loadStudents();
        await notificationsStore.fetchUnreadCount();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        deleting.value = false;
    }
}

async function approveStudent(student) {
    approvingId.value = student.id;
    delete approvalErrors[student.id];

    const trainerId = approvalSelections[student.id];

    if (! trainerId) {
        approvalErrors[student.id] = 'Selecione o professor responsável.';
        approvingId.value = null;
        return;
    }

    try {
        await api.put(`/students/${student.id}`, {
            status: 'active',
            trainer_id: Number(trainerId),
        });

        success.value = `${student.user?.name ?? 'Aluno'} aprovado com sucesso.`;

        if (selectedId.value === student.id) {
            form.status = 'active';
            form.trainer_id = String(trainerId);
            await loadActivityLogs(student.id);
        }

        scopeFilter.value = 'active';
        await loadStudents();
        await notificationsStore.fetchUnreadCount();
    } catch (err) {
        const parsed = extractError(err);
        approvalErrors[student.id] = firstValidationError(parsed.errors) ?? parsed.message;
    } finally {
        approvingId.value = null;
    }
}

function applyRouteFilters() {
    if (route.query.scope && ['active', 'pending', 'inactive'].includes(route.query.scope)) {
        scopeFilter.value = route.query.scope;
    } else if (route.query.status === 'pending') {
        scopeFilter.value = 'pending';
    }
}

watch(
    () => [route.query.gym_id, route.query.scope, route.query.id, route.query.status],
    async () => {
        applyRouteFilters();
        await loadStudents();
    },
);

onMounted(async () => {
    applyRouteFilters();

    try {
        await Promise.all([loadGyms(), loadTrainers(), loadStudents()]);
    } catch (err) {
        error.value = extractError(err).message;
        loading.value = false;
    }
});
</script>
