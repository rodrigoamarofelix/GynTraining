<template>
    <GuestLayout subtitle="Crie sua conta de aluno">
        <form class="space-y-4" @submit.prevent="submit">
            <UiAlert v-if="successMessage" :message="successMessage" variant="success" />
            <UiAlert v-if="errorMessage" :message="errorMessage" />

            <UiSelect
                v-model="form.gym_id"
                label="Academia"
                placeholder="Selecione sua academia"
                :options="gymOptions"
                :error="errors.gym_id"
            />
            <UiInput
                v-model="form.name"
                label="Nome"
                autocomplete="name"
                placeholder="Seu nome"
                :error="errors.name"
            />
            <UiInput
                v-model="form.email"
                label="E-mail"
                type="email"
                autocomplete="email"
                placeholder="seu@email.com"
                :error="errors.email"
            />
            <UiInput
                v-model="form.phone"
                label="Telefone (opcional)"
                autocomplete="tel"
                placeholder="(11) 99999-9999"
                :error="errors.phone"
            />
            <UiInput
                v-model="form.password"
                label="Senha"
                type="password"
                autocomplete="new-password"
                placeholder="••••••••"
                :error="errors.password"
            />
            <UiInput
                v-model="form.password_confirmation"
                label="Confirmar senha"
                type="password"
                autocomplete="new-password"
                placeholder="••••••••"
                :error="errors.password_confirmation"
            />

            <UiButton type="submit" size="lg" :loading="auth.loading" class="w-full">
                Criar conta
            </UiButton>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            Já tem conta?
            <RouterLink to="/login" class="font-semibold text-emerald-400 hover:text-emerald-300">
                Entrar
            </RouterLink>
        </p>
    </GuestLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import api, { extractData, extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiInput from '../../components/ui/UiInput.vue';
import UiSelect from '../../components/ui/UiSelect.vue';
import GuestLayout from '../../layouts/GuestLayout.vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();

const gyms = ref([]);

const form = reactive({
    gym_id: '',
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const gymOptions = computed(() => gyms.value.map((gym) => ({
    value: gym.id,
    label: gym.name,
})));

const errors = reactive({});
const errorMessage = ref('');
const successMessage = ref('');

onMounted(async () => {
    try {
        const response = await api.get('/auth/registration-gyms');
        gyms.value = extractData(response);

        if (gyms.value.length === 1) {
            form.gym_id = String(gyms.value[0].id);
        }
    } catch (error) {
        errorMessage.value = extractError(error).message;
    }
});

async function submit() {
    errorMessage.value = '';
    successMessage.value = '';
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        await auth.register({
            ...form,
            gym_id: Number(form.gym_id),
        });

        router.push({ path: '/login', query: { registered: 'pending' } });
    } catch (error) {
        errorMessage.value = error.message;
        Object.assign(errors, error.errors ?? {});
    }
}
</script>
