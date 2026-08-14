<template>
    <AppLayout :subtitle="auth.isGymAdmin ? 'Painel da academia' : 'Painel administrativo'">
        <AdminDashboard v-if="dashboard" :dashboard="dashboard" />
        <div v-else-if="loading" class="py-20 text-center text-slate-400">Carregando...</div>
        <UiAlert v-else :message="error" />
    </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import AdminDashboard from '../dashboard/AdminDashboard.vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();

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
