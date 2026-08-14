<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            @click.self="$emit('close')"
        >
            <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" />
            <div
                class="relative z-10 w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
            >
                <div class="flex items-start justify-between gap-4 border-b border-slate-800 px-6 py-4">
                    <div>
                        <h2 :id="titleId" class="text-lg font-semibold text-white">{{ title }}</h2>
                        <p v-if="subtitle" class="mt-1 text-sm text-slate-400">{{ subtitle }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                        aria-label="Fechar"
                        @click="$emit('close')"
                    >
                        ✕
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto px-6 py-4">
                    <slot />
                </div>

                <div v-if="$slots.footer" class="border-t border-slate-800 px-6 py-4">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { useId } from 'vue';

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
});

defineEmits(['close']);

const titleId = useId();
</script>
