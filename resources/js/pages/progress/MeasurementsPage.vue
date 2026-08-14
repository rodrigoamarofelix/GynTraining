<template>
    <AppLayout subtitle="Medidas corporais">
        <div class="grid gap-6 lg:grid-cols-2">
            <UiCard title="Nova medição">
                <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                    <UiAlert v-if="error" :message="error" />
                    <UiAlert v-if="success" :message="success" variant="success" />

                    <UiInput v-model="form.measured_at" label="Data" type="date" />
                    <UiInput v-model="form.weight" label="Peso (kg)" type="number" step="0.1" />
                    <UiInput v-model="form.height" label="Altura (cm)" type="number" step="0.1" />
                    <UiInput v-model="form.body_fat_percentage" label="% Gordura" type="number" step="0.1" />
                    <UiInput v-model="form.waist" label="Cintura (cm)" type="number" step="0.1" />
                    <UiInput v-model="form.notes" label="Observação" class="md:col-span-2" />

                    <div class="md:col-span-2">
                        <UiButton type="submit" :loading="saving">Salvar medição</UiButton>
                    </div>
                </form>
            </UiCard>

            <UiCard title="Histórico">
                <div v-if="loading" class="text-sm text-slate-400">Carregando...</div>
                <div v-else-if="items.length" class="space-y-3">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="rounded-xl border border-slate-800 bg-slate-950/60 p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-medium text-white">{{ formatDate(item.measured_at) }}</p>
                            <UiBadge variant="success">IMC {{ item.bmi ?? '—' }}</UiBadge>
                        </div>
                        <p class="mt-2 text-sm text-slate-400">
                            {{ formatWeight(item.weight) }} · {{ item.height ?? '—' }} cm
                        </p>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Nenhuma medição registrada.</p>
            </UiCard>
        </div>
    </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiInput from '../../components/ui/UiInput.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { formatDate, formatWeight } from '../../utils/format';

const items = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const success = ref('');

const form = reactive({
    measured_at: new Date().toISOString().slice(0, 10),
    weight: '',
    height: '',
    body_fat_percentage: '',
    waist: '',
    notes: '',
});

async function loadItems() {
    loading.value = true;

    try {
        const response = await api.get('/body-measurements', { params: { per_page: 20 } });
        items.value = extractData(response);
    } finally {
        loading.value = false;
    }
}

async function submit() {
    saving.value = true;
    error.value = '';
    success.value = '';

    const hasMeasurement = [form.weight, form.height, form.body_fat_percentage, form.waist]
        .some((value) => value !== '' && value != null);

    if (! hasMeasurement) {
        error.value = 'Informe ao menos uma medida ou o peso.';
        saving.value = false;

        return;
    }

    try {
        await api.post('/body-measurements', {
            measured_at: form.measured_at,
            weight: form.weight ? Number(form.weight) : undefined,
            height: form.height ? Number(form.height) : undefined,
            body_fat_percentage: form.body_fat_percentage ? Number(form.body_fat_percentage) : undefined,
            waist: form.waist ? Number(form.waist) : undefined,
            notes: form.notes || undefined,
        });

        success.value = 'Medição registrada com sucesso.';
        await loadItems();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        saving.value = false;
    }
}

onMounted(loadItems);
</script>
