<template>
    <AppLayout subtitle="Meus alunos">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <RouterLink to="/professor">
                <UiButton variant="ghost">← Painel</UiButton>
            </RouterLink>
            <RouterLink to="/professor/fichas/nova">
                <UiButton variant="secondary">Nova ficha</UiButton>
            </RouterLink>
        </div>

        <UiInput
            v-model="search"
            label="Buscar"
            placeholder="Nome ou e-mail..."
            class="mb-4"
            @input="debouncedLoad"
        />

        <div v-if="loading" class="py-20 text-center text-slate-400">Carregando alunos...</div>
        <UiAlert v-else-if="error" :message="error" />

        <div v-else-if="students.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <UiCard
                v-for="student in students"
                :key="student.id"
                :title="student.user?.name ?? 'Aluno'"
                :subtitle="student.user?.email"
            >
                <UiBadge :variant="student.status === 'active' ? 'success' : 'warning'">
                    {{ profileStatusLabel(student.status) }}
                </UiBadge>
                <p v-if="student.gym?.name" class="mt-2 text-xs text-slate-500">
                    {{ student.gym.name }}
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <RouterLink :to="{ name: 'trainer.student', params: { id: student.id } }">
                        <UiButton>Ver aluno</UiButton>
                    </RouterLink>
                    <RouterLink
                        :to="{ name: 'trainer.workout.create', query: { student_id: student.id } }"
                    >
                        <UiButton variant="secondary">Nova ficha</UiButton>
                    </RouterLink>
                </div>
            </UiCard>
        </div>

        <UiCard v-else title="Nenhum aluno">
            <p class="text-sm text-slate-400">
                Você ainda não tem alunos vinculados. Crie uma ficha para vincular um aluno da academia.
            </p>
        </UiCard>
    </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiInput from '../../components/ui/UiInput.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { profileStatusLabel } from '../../utils/format';

const students = ref([]);
const loading = ref(true);
const error = ref('');
const search = ref('');
let debounceTimer = null;

async function loadStudents() {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/students', {
            params: {
                per_page: 50,
                search: search.value || undefined,
            },
        });
        students.value = extractData(response);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loading.value = false;
    }
}

function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadStudents, 300);
}

onMounted(loadStudents);
</script>
