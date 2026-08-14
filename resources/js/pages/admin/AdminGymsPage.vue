<template>
    <AppLayout :subtitle="auth.isGymAdmin ? 'Minha academia' : 'Academias'">
        <div class="mb-6">
            <RouterLink to="/admin">
                <UiButton variant="ghost">← {{ auth.isGymAdmin ? 'Painel' : 'Admin' }}</UiButton>
            </RouterLink>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div v-if="isEditing || auth.isPlatformAdmin" class="space-y-6">
                <UiCard :title="formCardTitle">
                    <p v-if="auth.isPlatformAdmin && ! isEditing" class="mb-4 text-sm text-slate-400">
                        Cadastro interno pelo admin — entra como ativa.
                    </p>
                    <p v-else-if="isTrashed" class="mb-4 text-sm text-amber-300">
                        Academia excluída logicamente. Reativar restaura alunos e professores
                        desativados em cascata (quem foi excluído individualmente antes não volta).
                    </p>
                    <p v-else-if="auth.isPlatformAdmin" class="mb-4 text-sm text-slate-400">
                        Inativar pausa cadastros e seleções. Excluir desativa membros em cascata.
                    </p>
                    <p v-else class="mb-4 text-sm text-slate-400">
                        Edite os dados da sua academia e clique em salvar.
                    </p>
                    <form class="space-y-4" @submit.prevent="submit">
                        <UiAlert v-if="error" :message="error" />
                        <UiAlert v-if="success" :message="success" variant="success" />
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
                            placeholder="Academia Central"
                            :error="fieldErrors.name"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.description"
                            label="Descrição"
                            placeholder="Breve descrição"
                            :error="fieldErrors.description"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.address"
                            label="Endereço"
                            placeholder="Rua, número, bairro"
                            :error="fieldErrors.address"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.phone"
                            label="Telefone"
                            placeholder="(11) 99999-9999"
                            :error="fieldErrors.phone"
                            :disabled="isTrashed"
                        />
                        <UiInput
                            v-model="form.email"
                            label="E-mail"
                            type="email"
                            placeholder="contato@academia.com"
                            :error="fieldErrors.email"
                            :disabled="isTrashed"
                        />
                        <div class="flex flex-wrap gap-3">
                            <UiButton v-if="! isTrashed" type="submit" :loading="saving">
                                {{ isEditing ? 'Salvar alterações' : 'Cadastrar academia' }}
                            </UiButton>
                            <UiButton
                                v-if="isEditing"
                                type="button"
                                variant="secondary"
                                @click="openMembersModal(selectedGym)"
                            >
                                Ver membros
                            </UiButton>
                            <UiButton
                                v-if="isEditing && (isTrashed || form.status === 'inactive')"
                                type="button"
                                :loading="restoring"
                                @click="restoreGym"
                            >
                                Reativar academia
                            </UiButton>
                            <UiButton v-if="isEditing" type="button" variant="secondary" @click="cancelEdit">
                                Cancelar
                            </UiButton>
                            <UiButton
                                v-if="isEditing && auth.isPlatformAdmin && ! isTrashed"
                                type="button"
                                variant="danger"
                                :loading="deleting"
                                @click="removeGym"
                            >
                                Excluir academia
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
                                <UiBadge variant="info">{{ gymActivityActionLabel(log.action) }}</UiBadge>
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

            <UiCard title="Academias cadastradas">
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
                        Inativas
                    </UiButton>
                </div>

                <UiInput
                    v-model="search"
                    label="Buscar"
                    placeholder="Nome da academia..."
                    @input="debouncedLoad"
                />
                <div v-if="loading" class="mt-4 text-sm text-slate-400">Carregando...</div>
                <div v-else-if="gyms.length" class="mt-4 max-h-[32rem] space-y-3 overflow-y-auto">
                    <div
                        v-for="gym in gyms"
                        :key="gym.id"
                        class="cursor-pointer rounded-xl border bg-slate-950/60 p-4 transition hover:border-slate-700"
                        :class="selectedId === gym.id
                            ? 'border-emerald-500/60 ring-1 ring-emerald-500/30'
                            : 'border-slate-800'"
                        @click="selectGym(gym)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-white">{{ gym.name }}</p>
                                <p class="text-sm text-slate-400">{{ gym.address ?? '—' }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ gym.active_trainers_count ?? 0 }} professores ·
                                    {{ gym.active_students_count ?? 0 }} alunos ·
                                    {{ gym.exercises_count ?? 0 }} exercícios
                                </p>
                                <UiButton
                                    size="sm"
                                    variant="ghost"
                                    class="mt-3"
                                    @click.stop="openMembersModal(gym)"
                                >
                                    Ver membros
                                </UiButton>
                            </div>
                            <UiBadge :variant="gymBadgeVariant(gym)">
                                {{ gymBadgeLabel(gym) }}
                            </UiBadge>
                        </div>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-slate-400">Nenhuma academia encontrada.</p>
            </UiCard>
        </div>

        <UiModal
            :open="membersModalOpen"
            :title="membersModalGym?.name ?? 'Membros'"
            :subtitle="membersModalSubtitle"
            @close="closeMembersModal"
        >
            <div v-if="loadingMembers" class="text-sm text-slate-400">Carregando membros...</div>
            <UiAlert v-else-if="membersError" :message="membersError" />
            <div v-else class="space-y-6">
                <section>
                    <h3 class="mb-3 text-sm font-semibold text-white">
                        Professores ({{ gymMembers.trainers.length }})
                    </h3>
                    <div v-if="gymMembers.trainers.length" class="space-y-2">
                        <div
                            v-for="trainer in gymMembers.trainers"
                            :key="`trainer-${trainer.id}`"
                            class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <button
                                        type="button"
                                        class="text-left text-sm font-medium text-emerald-400 transition hover:text-emerald-300 hover:underline"
                                        @click="goToMember('trainer', trainer)"
                                    >
                                        {{ trainer.name ?? '—' }}
                                    </button>
                                    <p class="text-xs text-slate-400">{{ trainer.email ?? '—' }}</p>
                                    <p v-if="trainer.specialty" class="mt-1 text-xs text-emerald-400/80">
                                        {{ trainer.specialty }}
                                    </p>
                                </div>
                                <UiBadge :variant="memberBadgeVariant(trainer)">
                                    {{ memberBadgeLabel(trainer) }}
                                </UiBadge>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400">Nenhum professor vinculado.</p>
                </section>

                <section>
                    <h3 class="mb-3 text-sm font-semibold text-white">
                        Alunos ({{ gymMembers.students.length }})
                    </h3>
                    <div v-if="gymMembers.students.length" class="space-y-2">
                        <div
                            v-for="student in gymMembers.students"
                            :key="`student-${student.id}`"
                            class="rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <button
                                        type="button"
                                        class="text-left text-sm font-medium text-emerald-400 transition hover:text-emerald-300 hover:underline"
                                        @click="goToMember('student', student)"
                                    >
                                        {{ student.name ?? '—' }}
                                    </button>
                                    <p class="text-xs text-slate-400">{{ student.email ?? '—' }}</p>
                                </div>
                                <UiBadge :variant="memberBadgeVariant(student)">
                                    {{ memberBadgeLabel(student) }}
                                </UiBadge>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-400">Nenhum aluno vinculado.</p>
                </section>
            </div>

            <template #footer>
                <UiButton variant="secondary" @click="closeMembersModal">Fechar</UiButton>
            </template>
        </UiModal>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiInput from '../../components/ui/UiInput.vue';
import UiModal from '../../components/ui/UiModal.vue';
import UiSelect from '../../components/ui/UiSelect.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useAuthStore } from '../../stores/auth';
import {
    formatDateTime,
    gymActivityActionLabel,
    gymStatusLabel,
    profileStatusLabel,
} from '../../utils/format';

const router = useRouter();
const auth = useAuthStore();

const gyms = ref([]);
const activityLogs = ref([]);
const loading = ref(true);
const loadingActivity = ref(false);
const saving = ref(false);
const deleting = ref(false);
const restoring = ref(false);
const selectedId = ref(null);
const selectedGym = ref(null);
const error = ref('');
const success = ref('');
const search = ref('');
const scopeFilter = ref('active');
const fieldErrors = reactive({});
const membersModalOpen = ref(false);
const membersModalGym = ref(null);
const loadingMembers = ref(false);
const membersError = ref('');
const gymMembers = reactive({
    trainers: [],
    students: [],
});
let debounceTimer = null;

const form = reactive({
    status: 'active',
    name: '',
    description: '',
    address: '',
    phone: '',
    email: '',
});

const isEditing = computed(() => selectedId.value !== null);
const isTrashed = computed(() => Boolean(selectedGym.value?.deleted_at));

const formCardTitle = computed(() => {
    if (isEditing.value) {
        return auth.isPlatformAdmin ? 'Editar academia' : 'Minha academia';
    }

    return 'Nova academia';
});

const membersModalSubtitle = computed(() => {
    if (scopeFilter.value === 'inactive') {
        return 'Membros inativos ou excluídos desta academia';
    }

    return 'Professores e alunos ativos desta academia';
});

const statusOptions = [
    { value: 'active', label: 'Ativa' },
    { value: 'inactive', label: 'Inativa' },
];

function gymBadgeLabel(gym) {
    if (gym.deleted_at) {
        return 'Excluída';
    }

    return gymStatusLabel(gym.status);
}

function gymBadgeVariant(gym) {
    if (gym.deleted_at) {
        return 'danger';
    }

    if (gym.status === 'active') {
        return 'success';
    }

    return 'default';
}

function memberBadgeLabel(member) {
    if (member.deleted_at) {
        return 'Excluído';
    }

    return profileStatusLabel(member.status);
}

function memberBadgeVariant(member) {
    if (member.deleted_at) {
        return 'danger';
    }

    if (member.status === 'active') {
        return 'success';
    }

    return 'default';
}

function clearFieldErrors() {
    Object.keys(fieldErrors).forEach((key) => delete fieldErrors[key]);
}

function resetForm() {
    selectedId.value = null;
    selectedGym.value = null;
    activityLogs.value = [];
    form.name = '';
    form.description = '';
    form.address = '';
    form.phone = '';
    form.email = '';
    form.status = 'active';
}

function cancelEdit() {
    error.value = '';
    success.value = '';
    clearFieldErrors();
    resetForm();
}

async function loadActivityLogs(gymId) {
    loadingActivity.value = true;

    try {
        const response = await api.get(`/gyms/${gymId}/activity-logs`, { params: { per_page: 30 } });
        activityLogs.value = extractData(response);
    } catch {
        activityLogs.value = [];
    } finally {
        loadingActivity.value = false;
    }
}

function selectGym(gym) {
    error.value = '';
    success.value = '';
    clearFieldErrors();
    selectedId.value = gym.id;
    selectedGym.value = gym;
    form.status = gym.deleted_at ? 'inactive' : (gym.status ?? 'active');
    form.name = gym.name ?? '';
    form.description = gym.description ?? '';
    form.address = gym.address ?? '';
    form.phone = gym.phone ?? '';
    form.email = gym.email ?? '';
    loadActivityLogs(gym.id);
}

async function openMembersModal(gym) {
    if (! gym?.id) {
        return;
    }

    membersModalGym.value = gym;
    membersModalOpen.value = true;
    loadingMembers.value = true;
    membersError.value = '';
    gymMembers.trainers = [];
    gymMembers.students = [];

    try {
        const response = await api.get(`/gyms/${gym.id}/members`, {
            params: { scope: scopeFilter.value },
        });
        const data = extractData(response);

        gymMembers.trainers = normalizeMemberList(data?.trainers);
        gymMembers.students = normalizeMemberList(data?.students);
    } catch (err) {
        gymMembers.trainers = [];
        gymMembers.students = [];
        membersError.value = extractError(err).message;
    } finally {
        loadingMembers.value = false;
    }
}

function normalizeMemberList(value) {
    if (Array.isArray(value)) {
        return value;
    }

    if (value && typeof value === 'object') {
        return Object.values(value);
    }

    return [];
}

function memberScope(member) {
    if (member.deleted_at || member.status === 'inactive') {
        return 'inactive';
    }

    if (member.status === 'pending') {
        return 'pending';
    }

    return 'active';
}

function goToMember(type, member) {
    const gymId = membersModalGym.value?.id;

    if (! gymId || ! member?.id) {
        return;
    }

    const query = {
        gym_id: String(gymId),
        gym_name: membersModalGym.value?.name ?? undefined,
        scope: memberScope(member),
        id: String(member.id),
    };

    closeMembersModal();

    router.push({
        path: type === 'trainer' ? '/admin/professores' : '/admin/alunos',
        query,
    });
}

function closeMembersModal() {
    membersModalOpen.value = false;
    membersModalGym.value = null;
    membersError.value = '';
    gymMembers.trainers = [];
    gymMembers.students = [];
}

function setScopeFilter(value) {
    scopeFilter.value = value;
    loadGyms();
}

async function loadGyms() {
    loading.value = true;

    try {
        const response = await api.get('/gyms', {
            params: {
                search: search.value || undefined,
                scope: scopeFilter.value,
                per_page: 50,
            },
        });
        gyms.value = extractData(response);

        if (! auth.isPlatformAdmin && gyms.value.length === 1 && ! selectedId.value) {
            selectGym(gyms.value[0]);
        }

        if (selectedId.value) {
            const current = gyms.value.find((gym) => gym.id === selectedId.value);

            if (current) {
                selectGym(current);
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
    debounceTimer = setTimeout(loadGyms, 300);
}

function buildPayload() {
    const payload = {
        name: form.name,
        description: form.description || null,
        address: form.address || null,
        phone: form.phone || null,
        email: form.email || null,
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
            await api.put(`/gyms/${selectedId.value}`, payload);
            success.value = 'Academia atualizada com sucesso.';
            await loadActivityLogs(selectedId.value);
        } else {
            await api.post('/gyms', payload);
            success.value = 'Academia cadastrada com sucesso.';
            resetForm();
        }

        await loadGyms();
    } catch (err) {
        const parsed = extractError(err);
        error.value = parsed.message;
        Object.assign(fieldErrors, parsed.errors ?? {});
    } finally {
        saving.value = false;
    }
}

async function restoreGym() {
    if (! selectedId.value) {
        return;
    }

    restoring.value = true;
    error.value = '';
    success.value = '';

    try {
        if (isTrashed.value) {
            await api.post(`/gyms/${selectedId.value}/restore`);
        } else {
            await api.put(`/gyms/${selectedId.value}`, { status: 'active' });
        }

        success.value = 'Academia reativada com sucesso.';
        scopeFilter.value = 'active';
        await loadGyms();
        await loadActivityLogs(selectedId.value);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        restoring.value = false;
    }
}

async function removeGym() {
    if (! selectedId.value || isTrashed.value) {
        return;
    }

    const gymName = form.name || 'esta academia';
    const students = selectedGym.value?.active_students_count ?? 0;
    const trainers = selectedGym.value?.active_trainers_count ?? 0;

    let message = `Excluir ${gymName}? A academia será removida logicamente.`;

    if (students > 0 || trainers > 0) {
        message += ` ${trainers} professor(es) e ${students} aluno(s) ativos serão desativados em cascata.`;
        message += ' Quem já foi excluído individualmente antes não será afetado.';
    }

    if (! window.confirm(message)) {
        return;
    }

    deleting.value = true;
    error.value = '';
    success.value = '';

    try {
        await api.delete(`/gyms/${selectedId.value}`);
        success.value = 'Academia excluída com sucesso.';
        scopeFilter.value = 'inactive';
        resetForm();
        await loadGyms();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        deleting.value = false;
    }
}

onMounted(loadGyms);
</script>
