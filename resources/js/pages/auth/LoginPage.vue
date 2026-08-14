<template>
    <GuestLayout subtitle="Entre na sua conta">
        <form class="space-y-4" @submit.prevent="submit">
            <UiAlert v-if="successMessage" :message="successMessage" variant="success" />
            <UiAlert v-if="errorMessage" :message="errorMessage" />

            <UiInput
                v-model="form.email"
                label="E-mail"
                type="email"
                autocomplete="email"
                placeholder="seu@email.com"
                :error="errors.email"
            />
            <UiInput
                v-model="form.password"
                label="Senha"
                type="password"
                autocomplete="current-password"
                placeholder="••••••••"
                :error="errors.password"
            />

            <UiButton type="submit" size="lg" :loading="auth.loading" class="w-full">
                Entrar
            </UiButton>
        </form>

        <p class="mt-4 text-center text-sm">
            <RouterLink to="/forgot-password" class="font-semibold text-emerald-400 hover:text-emerald-300">
                Esqueci minha senha
            </RouterLink>
        </p>

        <p class="mt-4 text-center text-sm text-slate-400">
            Não tem conta?
            <RouterLink to="/register" class="font-semibold text-emerald-400 hover:text-emerald-300">
                Cadastre-se
            </RouterLink>
        </p>
    </GuestLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiInput from '../../components/ui/UiInput.vue';
import GuestLayout from '../../layouts/GuestLayout.vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const form = reactive({
    email: '',
    password: '',
});

const errors = reactive({});
const errorMessage = ref('');

const successMessage = computed(() => {
    if (route.query.registered === 'pending') {
        return 'Cadastro enviado! Aguarde a aprovação da academia para entrar.';
    }

    return '';
});

async function submit() {
    errorMessage.value = '';
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        await auth.login(form);
        router.push(route.query.redirect ?? '/');
    } catch (error) {
        errorMessage.value = error.message;

        Object.assign(errors, error.errors ?? {});
    }
}
</script>
