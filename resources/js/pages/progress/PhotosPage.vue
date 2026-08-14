<template>
    <AppLayout subtitle="Fotos de evolução">
        <UiCard v-if="auth.isStudent" title="Enviar foto" class="mb-6">
            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="upload">
                <UiAlert v-if="uploadError" :message="uploadError" />
                <UiAlert v-if="uploadSuccess" :message="uploadSuccess" variant="success" />

                <UiSelect
                    v-model="uploadForm.category"
                    label="Posição"
                    :options="categoryOptions"
                />
                <UiSelect
                    v-model="uploadForm.visibility"
                    label="Visibilidade"
                    :options="visibilityOptions"
                />
                <UiInput v-model="uploadForm.taken_at" label="Data" type="date" />
                <label class="block space-y-2 md:col-span-2">
                    <span class="text-sm font-medium text-slate-300">Imagem</span>
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/*"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-950"
                        @change="onFileChange"
                    >
                </label>
                <UiInput v-model="uploadForm.notes" label="Observação" class="md:col-span-2" />
                <div class="md:col-span-2">
                    <UiButton type="submit" :loading="uploading">Enviar foto</UiButton>
                </div>
            </form>
        </UiCard>

        <UiCard title="Suas fotos">
            <div v-if="loading" class="text-sm text-slate-400">Carregando...</div>
            <UiAlert v-else-if="error" :message="error" />

            <div v-else-if="photos.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="photo in photos"
                    :key="photo.id"
                    class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950/60"
                >
                    <div class="aspect-[3/4] bg-slate-900">
                        <img
                            v-if="photo.photo_url"
                            :src="photo.photo_url"
                            :alt="photo.category"
                            class="h-full w-full object-cover"
                        >
                        <div v-else class="flex h-full items-center justify-center text-slate-600">
                            Sem preview
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="flex flex-wrap gap-2">
                            <UiBadge>{{ categoryLabel(photo.category) }}</UiBadge>
                            <UiBadge variant="info">{{ visibilityLabel(photo.visibility) }}</UiBadge>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">{{ formatDate(photo.taken_at) }}</p>
                        <UiButton
                            v-if="auth.isStudent"
                            class="mt-3"
                            size="sm"
                            variant="danger"
                            :loading="deletingId === photo.id"
                            @click="removePhoto(photo.id)"
                        >
                            Excluir
                        </UiButton>
                    </div>
                </div>
            </div>

            <p v-else class="text-sm text-slate-400">Nenhuma foto registrada.</p>
        </UiCard>
    </AppLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api, { extractData, extractError, postFormData } from '../../api/client';
import UiAlert from '../../components/ui/UiAlert.vue';
import UiBadge from '../../components/ui/UiBadge.vue';
import UiButton from '../../components/ui/UiButton.vue';
import UiCard from '../../components/ui/UiCard.vue';
import UiInput from '../../components/ui/UiInput.vue';
import UiSelect from '../../components/ui/UiSelect.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useAuthStore } from '../../stores/auth';
import { formatDate } from '../../utils/format';

const auth = useAuthStore();
const photos = ref([]);
const loading = ref(true);
const uploading = ref(false);
const deletingId = ref(null);
const error = ref('');
const uploadError = ref('');
const uploadSuccess = ref('');
const selectedFile = ref(null);
const fileInput = ref(null);

const uploadForm = reactive({
    category: 'front',
    visibility: 'trainer',
    taken_at: new Date().toISOString().slice(0, 10),
    notes: '',
});

const categoryOptions = [
    { value: 'front', label: 'Frente' },
    { value: 'back', label: 'Costas' },
    { value: 'side', label: 'Lado' },
];

const visibilityOptions = [
    { value: 'private', label: 'Somente eu' },
    { value: 'trainer', label: 'Professor' },
    { value: 'gym', label: 'Academia' },
];

function categoryLabel(value) {
    return categoryOptions.find((item) => item.value === value)?.label ?? value;
}

function visibilityLabel(value) {
    return visibilityOptions.find((item) => item.value === value)?.label ?? value;
}

function onFileChange(event) {
    selectedFile.value = event.target.files?.[0] ?? null;
}

function resetUploadForm() {
    selectedFile.value = null;
    uploadForm.notes = '';

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

async function loadPhotos() {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/progress-photos', { params: { per_page: 20 } });
        photos.value = extractData(response);
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        loading.value = false;
    }
}

async function upload() {
    if (! selectedFile.value) {
        uploadError.value = 'Selecione uma imagem.';

        return;
    }

    uploading.value = true;
    uploadError.value = '';
    uploadSuccess.value = '';

    const formData = new FormData();
    formData.append('category', uploadForm.category);
    formData.append('visibility', uploadForm.visibility);
    formData.append('taken_at', uploadForm.taken_at);
    formData.append('photo', selectedFile.value);

    if (uploadForm.notes) {
        formData.append('notes', uploadForm.notes);
    }

    try {
        await postFormData('/progress-photos', formData);
        uploadSuccess.value = 'Foto enviada com sucesso.';
        resetUploadForm();
        await loadPhotos();
    } catch (err) {
        uploadError.value = extractError(err).message;
    } finally {
        uploading.value = false;
    }
}

async function removePhoto(photoId) {
    if (! window.confirm('Excluir esta foto?')) {
        return;
    }

    deletingId.value = photoId;
    error.value = '';

    try {
        await api.delete(`/progress-photos/${photoId}`);
        await loadPhotos();
    } catch (err) {
        error.value = extractError(err).message;
    } finally {
        deletingId.value = null;
    }
}

onMounted(loadPhotos);
</script>
