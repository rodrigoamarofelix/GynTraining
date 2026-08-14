<template>
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <UiStat label="Treinos no mês" :value="dashboard.stats?.workouts_this_month ?? 0" />
            <UiStat
                label="Tempo treinado"
                :value="formatDuration(dashboard.stats?.total_training_seconds ?? 0)"
            />
            <UiStat label="Frequência semanal" :value="`${dashboard.stats?.weekly_frequency ?? 0}x`" />
            <UiStat label="Peso atual" :value="formatWeight(dashboard.stats?.current_weight)" />
        </section>

        <UiCard
            v-if="dashboard.active_session"
            eyebrow="Em andamento"
            title="Treino ativo"
            subtitle="Continue de onde parou"
        >
            <p class="text-sm text-slate-300">
                {{ dashboard.active_session.workout_day_name ?? 'Treino' }}
            </p>
            <RouterLink
                :to="{
                    name: 'workout.execute',
                    params: {
                        planId: dashboard.active_session.workout_plan_id,
                        dayId: dashboard.active_session.workout_day_id,
                    },
                }"
                class="mt-4 inline-block"
            >
                <UiButton>Continuar treino</UiButton>
            </RouterLink>
        </UiCard>

        <div class="grid gap-6 lg:grid-cols-2">
            <UiCard
                eyebrow="Treino do dia"
                :title="dashboard.today_workout?.name ?? 'Sem treino hoje'"
                :subtitle="dashboard.today_workout?.workout_plan_name"
            >
                <p v-if="dashboard.today_workout?.description" class="text-sm text-slate-400">
                    {{ dashboard.today_workout.description }}
                </p>
                <RouterLink
                    v-if="dashboard.today_workout"
                    :to="{
                        name: 'workout.execute',
                        params: {
                            planId: dashboard.today_workout.workout_plan_id,
                            dayId: dashboard.today_workout.id,
                        },
                    }"
                    class="mt-4 inline-block"
                >
                    <UiButton size="lg">Iniciar treino</UiButton>
                </RouterLink>
            </UiCard>

            <UiCard eyebrow="Último treino" :title="dashboard.last_workout?.workout_day_name ?? '—'">
                <p v-if="dashboard.last_workout?.finished_at" class="text-sm text-slate-400">
                    {{ formatDateTime(dashboard.last_workout.finished_at) }}
                    · {{ formatDuration(dashboard.last_workout.duration_seconds ?? 0) }}
                </p>
                <p v-else class="text-sm text-slate-400">Nenhum treino concluído ainda.</p>
            </UiCard>
        </div>

        <UiCard eyebrow="Evolução" title="Peso recente">
            <div v-if="dashboard.weight_evolution?.length" class="space-y-2">
                <div
                    v-for="item in dashboard.weight_evolution.slice(0, 4)"
                    :key="item.measured_at"
                    class="flex items-center justify-between rounded-lg bg-slate-950/60 px-3 py-2 text-sm"
                >
                    <span>{{ formatDate(item.measured_at) }}</span>
                    <span class="text-slate-300">{{ formatWeight(item.weight) }}</span>
                </div>
            </div>
            <p v-else class="text-sm text-slate-400">Registre medidas para acompanhar sua evolução.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <RouterLink to="/evolucao"><UiButton variant="secondary">Ver evolução</UiButton></RouterLink>
                <RouterLink to="/medidas"><UiButton variant="ghost">Nova medição</UiButton></RouterLink>
            </div>
        </UiCard>

        <UiCard eyebrow="Metas" title="Suas metas ativas">
            <div v-if="dashboard.goals?.length" class="space-y-3">
                <div
                    v-for="goal in dashboard.goals"
                    :key="goal.id"
                    class="rounded-xl border border-slate-800 bg-slate-950/60 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-medium text-white">{{ goal.name }}</p>
                        <UiBadge variant="success">{{ goal.progress_percentage }}%</UiBadge>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
                        <div
                            class="h-full rounded-full bg-emerald-500"
                            :style="{ width: `${goal.progress_percentage}%` }"
                        />
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-slate-400">Nenhuma meta ativa.</p>
            <RouterLink to="/metas" class="mt-4 inline-block text-sm font-semibold text-emerald-400">
                Gerenciar metas →
            </RouterLink>
        </UiCard>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiStat from '../../components/ui/UiStat.vue';
import { formatDate, formatDateTime, formatDuration, formatWeight } from '../../utils/format';

defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
});
</script>
