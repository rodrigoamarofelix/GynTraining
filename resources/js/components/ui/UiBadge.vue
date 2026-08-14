<template>
    <span
        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
        :class="toneClasses[variant] ?? toneClasses.default"
    >
        <slot />
    </span>
</template>

<script setup>
defineProps({
    variant: {
        type: String,
        default: 'default',
    },
});

const toneClasses = {
    default: 'bg-slate-800 text-slate-300',
    success: 'bg-emerald-500/15 text-emerald-300',
    warning: 'bg-amber-500/15 text-amber-300',
    danger: 'bg-red-500/15 text-red-300',
    info: 'bg-sky-500/15 text-sky-300',
};
</script>
