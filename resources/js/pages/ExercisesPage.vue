<template>
    <AppLayout subtitle="Catálogo de exercícios">
        <UiInput
            v-model="search"
            label="Buscar exercício"
            placeholder="Supino, agachamento..."
            class="mb-6"
            @input="debouncedLoad"
        />

        <div v-if="loading" class="py-20 text-center text-slate-400">Carregando exercícios...</div>
        <UiAlert v-else-if="error" :message="error" />

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <UiCard
                v-for="exercise in exercises"
                :key="exercise.id"
                :title="exercise.name"
                :subtitle="exercise.muscle_group?.name"
            >
                <p class="line-clamp-3 text-sm text-slate-400">{{ exercise.description }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <UiBadge>{{ exercise.difficulty ?? '—' }}</UiBadge>
                    <UiBadge variant="info">{{ exercise.equipment ?? 'Livre' }}</UiBadge>
                </div>
            </UiCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api, { extractData, extractError } from '../api/client';
import UiAlert from '../components/ui/UiAlert.vue';
import UiBadge from '../components/ui/UiBadge.vue';
import UiCard from '../components/ui/UiCard.vue';
import UiInput from '../components/ui/UiInput.vue';
import AppLayout from '../layouts/AppLayout.vue';

const exercises = ref([]);
const loading = ref(true);
const error = ref('');
const search = ref('');
let debounceTimer = null;

async function loadExercises() {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/exercises', {
            params: {
                search: search.value || undefined,
                per_page: 30,
            },
        });
        exercises.value = extractData(response);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loading.value = false;
    }
}

function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadExercises, 300);
}

onMounted(loadExercises);
</script>
