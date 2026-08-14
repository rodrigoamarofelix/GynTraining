<template>
    <AppLayout subtitle="Painel do professor">
        <TrainerDashboard v-if="dashboard" :dashboard="dashboard" />
        <div v-else-if="loading" class="py-20 text-center text-slate-400">Carregando...</div>
        <UiAlert v-else :message="error" />
    </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import TrainerDashboard from '../dashboard/TrainerDashboard.vue';

const dashboard = ref(null);
const loading = ref(true);
const error = ref('');

onMounted(async () => {
    try {
        const response = await api.get('/dashboard');
        dashboard.value = extractData(response);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loading.value = false;
    }
});
</script>
