<template>
    <AppLayout subtitle="Meu perfil">
        <UiCard :title="auth.displayName" :subtitle="auth.user?.email">
            <form class="space-y-4" @submit.prevent="saveProfile">
                <UiAlert v-if="profileError" :message="profileError" />
                <UiAlert v-if="profileSuccess" :message="profileSuccess" variant="success" />

                <UiInput v-model="profileForm.name" label="Nome" required />
                <UiInput v-model="profileForm.phone" label="Telefone" placeholder="(62) 99999-9999" />

                <div class="flex flex-wrap gap-2 text-sm text-slate-400">
                    <span>Status:</span>
                    <UiBadge variant="success">{{ profileStatusLabel(auth.user?.status) }}</UiBadge>
                </div>
                <p class="text-sm text-slate-400">
                    Papéis: {{ auth.roles.map(roleLabel).join(', ') }}
                </p>

                <UiButton type="submit" :loading="savingProfile">Salvar perfil</UiButton>
            </form>
        </UiCard>

        <UiCard title="Alterar senha" class="mt-6">
            <form class="space-y-4" @submit.prevent="savePassword">
                <UiAlert v-if="passwordError" :message="passwordError" />
                <UiAlert v-if="passwordSuccess" :message="passwordSuccess" variant="success" />

                <UiInput
                    v-model="passwordForm.current_password"
                    label="Senha atual"
                    type="password"
                    autocomplete="current-password"
                />
                <UiInput
                    v-model="passwordForm.password"
                    label="Nova senha"
                    type="password"
                    autocomplete="new-password"
                />
                <UiInput
                    v-model="passwordForm.password_confirmation"
                    label="Confirmar nova senha"
                    type="password"
                    autocomplete="new-password"
                />

                <UiButton type="submit" variant="secondary" :loading="savingPassword">
                    Atualizar senha
                </UiButton>
            </form>
        </UiCard>

        <UiButton class="mt-6" variant="danger" @click="logout">Sair da conta</UiButton>
    </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api, { extractData, extractError } from '../api/client';
import UiAlert from '../components/ui/UiAlert.vue';
import UiBadge from '../components/ui/UiBadge.vue';
import UiButton from '../components/ui/UiButton.vue';
import UiCard from '../components/ui/UiCard.vue';
import UiInput from '../components/ui/UiInput.vue';
import AppLayout from '../layouts/AppLayout.vue';
import { useAuthStore } from '../stores/auth';
import { profileStatusLabel, roleLabel } from '../utils/format';

const auth = useAuthStore();
const router = useRouter();

const profileForm = reactive({ name: '', phone: '' });
const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const savingProfile = ref(false);
const savingPassword = ref(false);
const profileError = ref('');
const profileSuccess = ref('');
const passwordError = ref('');
const passwordSuccess = ref('');

onMounted(() => {
    profileForm.name = auth.user?.name ?? '';
    profileForm.phone = auth.user?.phone ?? '';
});

async function saveProfile() {
    savingProfile.value = true;
    profileError.value = '';
    profileSuccess.value = '';

    try {
        const response = await api.put('/auth/profile', {
            name: profileForm.name,
            phone: profileForm.phone || null,
        });
        auth.user = extractData(response);
        profileSuccess.value = 'Perfil atualizado.';
    } catch (error) {
        profileError.value = extractError(error).message;
    } finally {
        savingProfile.value = false;
    }
}

async function savePassword() {
    savingPassword.value = true;
    passwordError.value = '';
    passwordSuccess.value = '';

    try {
        await api.put('/auth/password', passwordForm);
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
        passwordSuccess.value = 'Senha atualizada.';
    } catch (error) {
        passwordError.value = extractError(error).message;
    } finally {
        savingPassword.value = false;
    }
}

async function logout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>
