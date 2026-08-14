<template>
    <div class="space-y-6">
        <div class="flex flex-wrap gap-3">
            <RouterLink to="/professor/alunos">
                <UiButton>Gerenciar alunos</UiButton>
            </RouterLink>
            <RouterLink to="/professor/fichas/nova">
                <UiButton variant="secondary">Nova ficha</UiButton>
            </RouterLink>
        </div>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <UiStat label="Total de alunos" :value="dashboard.stats?.total_students ?? 0" />
            <UiStat label="Alunos ativos" :value="dashboard.stats?.active_students ?? 0" />
            <UiStat label="Treinos no mês" :value="dashboard.stats?.workouts_this_month ?? 0" />
            <UiStat label="Sem treinar" :value="dashboard.stats?.students_without_workout ?? 0" />
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <UiCard eyebrow="Alertas" title="Alunos que precisam de atenção">
                <div v-if="dashboard.students_needing_attention?.length" class="space-y-3">
                    <RouterLink
                        v-for="student in dashboard.students_needing_attention"
                        :key="student.student_id"
                        :to="{ name: 'trainer.student', params: { id: student.student_id } }"
                        class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950/60 p-4 transition hover:border-slate-700"
                    >
                        <div>
                            <p class="font-medium text-white">{{ student.student_name }}</p>
                            <p class="text-xs text-slate-400">
                                {{
                                    student.days_since_last_workout === null
                                        ? 'Nunca treinou'
                                        : `Sem treinar há ${student.days_since_last_workout} dias`
                                }}
                            </p>
                        </div>
                        <UiBadge variant="warning">
                            {{ student.days_since_last_workout ?? '!' }}
                        </UiBadge>
                    </RouterLink>
                </div>
                <p v-else class="text-sm text-slate-400">Nenhum alerta no momento.</p>
            </UiCard>

            <UiCard eyebrow="Recentes" title="Últimos treinos">
                <div v-if="dashboard.recent_workouts?.length" class="space-y-3">
                    <div
                        v-for="session in dashboard.recent_workouts"
                        :key="session.id"
                        class="rounded-xl border border-slate-800 bg-slate-950/60 p-4"
                    >
                        <p class="font-medium text-white">{{ session.student_name }}</p>
                        <p class="text-xs text-slate-400">
                            {{ session.workout_day }} · {{ formatDateTime(session.finished_at) }}
                        </p>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Nenhum treino recente.</p>
            </UiCard>
        </div>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiStat from '../../components/ui/UiStat.vue';
import { formatDateTime } from '../../utils/format';

defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
});
</script>
