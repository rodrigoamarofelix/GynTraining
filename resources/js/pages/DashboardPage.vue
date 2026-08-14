<template>
    <AppLayout :subtitle="dashboard?.role ? roleLabel(dashboard.role) : 'Dashboard'">
        <div v-if="loading" class="py-20 text-center text-slate-400">Carregando dashboard...</div>

        <UiAlert v-else-if="error" :message="error" />

        <template v-else-if="dashboard">
            <StudentDashboard v-if="dashboard.role === 'student'" :dashboard="dashboard" />
            <TrainerDashboard v-else-if="dashboard.role === 'trainer'" :dashboard="dashboard" />
            <AdminDashboard v-else :dashboard="dashboard" />
        </template>
    </AppLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api, { extractData, extractError } from '../api/client';
import UiAlert from '../components/ui/UiAlert.vue';
import AppLayout from '../layouts/AppLayout.vue';
import { roleLabel } from '../utils/format';
import AdminDashboard from './dashboard/AdminDashboard.vue';
import StudentDashboard from './dashboard/StudentDashboard.vue';
import TrainerDashboard from './dashboard/TrainerDashboard.vue';

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
