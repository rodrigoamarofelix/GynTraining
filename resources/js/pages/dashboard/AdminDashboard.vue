<template>
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <UiStat
                :label="auth.isGymAdmin ? 'Minha academia' : 'Academias'"
                :value="dashboard.stats?.total_gyms ?? 0"
            />
            <UiStat label="Alunos" :value="dashboard.stats?.total_students ?? 0" />
            <UiStat label="Professores" :value="dashboard.stats?.total_trainers ?? 0" />
            <UiStat label="Treinos no mês" :value="dashboard.stats?.workouts_this_month ?? 0" />
        </section>

        <UiCard
            eyebrow="Gestão"
            :title="auth.isGymAdmin ? 'Painel da academia' : 'Painel administrativo'"
        >
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <RouterLink to="/admin/academias">
                    <UiButton variant="secondary" class="w-full">
                        {{ auth.isGymAdmin ? 'Minha academia' : 'Academias' }}
                    </UiButton>
                </RouterLink>
                <RouterLink to="/admin/alunos">
                    <UiButton variant="secondary" class="w-full">Alunos</UiButton>
                </RouterLink>
                <RouterLink to="/admin/professores">
                    <UiButton variant="secondary" class="w-full">Professores</UiButton>
                </RouterLink>
                <RouterLink to="/admin/exercicios">
                    <UiButton variant="secondary" class="w-full">Exercícios</UiButton>
                </RouterLink>
                <RouterLink v-if="auth.isPlatformAdmin" to="/admin/grupos">
                    <UiButton variant="secondary" class="w-full">Grupos musculares</UiButton>
                </RouterLink>
                <RouterLink v-if="auth.isPlatformAdmin" to="/admin/categorias">
                    <UiButton variant="secondary" class="w-full">Categorias</UiButton>
                </RouterLink>
            </div>
            <a
                v-if="auth.isPlatformAdmin"
                href="/docs/api"
                target="_blank"
                rel="noopener"
                class="mt-4 inline-block"
            >
                <UiButton variant="ghost">Documentação API</UiButton>
            </a>
        </UiCard>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiStat from '../../components/ui/UiStat.vue';
import { useAuthStore } from '../../stores/auth';

defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
});

const auth = useAuthStore();
</script>
