<template>
    <GuestLayout subtitle="Nova senha">
        <form class="space-y-4" @submit.prevent="submit">
            <UiAlert v-if="errorMessage" :message="errorMessage" />
            <UiAlert v-if="successMessage" :message="successMessage" variant="success" />

            <UiInput
                v-model="form.email"
                label="E-mail"
                type="email"
                autocomplete="email"
                :error="errors.email"
            />
            <UiInput
                v-model="form.password"
                label="Nova senha"
                type="password"
                autocomplete="new-password"
                :error="errors.password"
            />
            <UiInput
                v-model="form.password_confirmation"
                label="Confirmar senha"
                type="password"
                autocomplete="new-password"
            />

            <UiButton type="submit" size="lg" :loading="loading" class="w-full">
                Redefinir senha
            </UiButton>
        </form>

        <p class="mt-6 text-center text-sm text-slate-400">
            <RouterLink to="/login" class="font-semibold text-emerald-400 hover:text-emerald-300">
                Voltar ao login
            </RouterLink>
        </p>
    </GuestLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import api, { extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiInput from '../../components/ui/UiInput.vue';
import GuestLayout from '../../layouts/GuestLayout.vue';

const route = useRoute();
const router = useRouter();

const form = reactive({
    email: '',
    password: '',
    password_confirmation: '',
    token: '',
});

const errors = reactive({});
const errorMessage = ref('');
const successMessage = ref('');
const loading = ref(false);

onMounted(() => {
    form.email = String(route.query.email ?? '');
    form.token = String(route.query.token ?? '');
});

async function submit() {
    errorMessage.value = '';
    successMessage.value = '';
    Object.keys(errors).forEach((key) => delete errors[key]);
    loading.value = true;

    try {
        await api.post('/auth/reset-password', form);
        successMessage.value = 'Senha redefinida com sucesso. Redirecionando...';
        setTimeout(() => router.push('/login'), 1500);
    } catch (error) {
        const parsed = extractError(error);
        errorMessage.value = parsed.message;
        Object.assign(errors, parsed.errors ?? {});
    } finally {
        loading.value = false;
    }
}
</script>
