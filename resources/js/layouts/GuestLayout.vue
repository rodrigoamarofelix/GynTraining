<template>
    <div class="flex min-h-dvh items-center justify-center bg-slate-950 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-white">
                    Gyn<span class="text-emerald-400">Training</span>
                </h1>
                <p class="mt-2 text-sm text-slate-400">{{ subtitle }}</p>
            </div>
            <UiCard>
                <slot />
            </UiCard>
        </div>
    </div>
</template>

<script setup>
import UiCard from '../components/ui/UiCard.vue';

defineProps({
    subtitle: {
        type: String,
        default: 'Seu treino, sua evolução',
    },
});
</script>
