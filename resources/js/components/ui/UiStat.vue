<template>
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ label }}</p>
        <p class="mt-2 text-2xl font-bold text-white">{{ value }}</p>
        <p v-if="hint" class="mt-1 text-xs text-slate-400">{{ hint }}</p>
    </div>
</template>

<script setup>
defineProps({
    label: String,
    value: [String, Number],
    hint: String,
});
</script>
