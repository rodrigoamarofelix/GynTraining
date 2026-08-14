<template>
    <div
        class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-lg shadow-black/20 backdrop-blur"
        :class="paddingClass"
    >
        <div v-if="title || $slots.header" class="mb-4 flex items-start justify-between gap-3">
            <div>
                <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-wider text-emerald-400">
                    {{ eyebrow }}
                </p>
                <h2 v-if="title" class="text-lg font-semibold text-white">{{ title }}</h2>
                <p v-if="subtitle" class="mt-1 text-sm text-slate-400">{{ subtitle }}</p>
            </div>
            <slot name="header" />
        </div>
        <slot />
    </div>
</template>

<script setup>
defineProps({
    title: String,
    subtitle: String,
    eyebrow: String,
    paddingClass: {
        type: String,
        default: '',
    },
});
</script>
