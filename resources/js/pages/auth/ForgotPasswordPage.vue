<template>
    <GuestLayout subtitle="Recuperar senha">
        <form class="space-y-4" @submit.prevent="submit">
            <UiAlert v-if="errorMessage" :message="errorMessage" />
            <UiAlert v-if="successMessage" :message="successMessage" variant="success" />

            <p class="text-sm text-slate-400">
                Informe seu e-mail. Se existir uma conta, enviaremos instruções para redefinir a senha.
            </p>

            <UiInput
                v-model="form.email"
                label="E-mail"
                type="email"
                autocomplete="email"
                :error="errors.email"
            />

            <UiButton type="submit" size="lg" :loading="loading" class="w-full">
                Enviar link
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
import { reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import api, { extractError } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiInput from '../../components/ui/UiInput.vue';
import GuestLayout from '../../layouts/GuestLayout.vue';

const form = reactive({ email: '' });
const errors = reactive({});
const errorMessage = ref('');
const successMessage = ref('');
const loading = ref(false);

async function submit() {
    errorMessage.value = '';
    successMessage.value = '';
    Object.keys(errors).forEach((key) => delete errors[key]);
    loading.value = true;

    try {
        await api.post('/auth/forgot-password', form);
        successMessage.value = 'Se o e-mail existir, você receberá instruções em breve.';
    } catch (error) {
        const parsed = extractError(error);
        errorMessage.value = parsed.message;
        Object.assign(errors, parsed.errors ?? {});
    } finally {
        loading.value = false;
    }
}
</script>
