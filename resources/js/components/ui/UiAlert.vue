<template>
    <div
        class="rounded-xl border px-4 py-3 text-sm"
        :class="variantClasses[variant] ?? variantClasses.error"
    >
        {{ message }}
    </div>
</template>

<script setup>
defineProps({
    message: {
        type: String,
        required: true,
    },
    variant: {
        type: String,
        default: 'error',
    },
});

const variantClasses = {
    error: 'border-red-500/30 bg-red-500/10 text-red-300',
    success: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
    info: 'border-sky-500/30 bg-sky-500/10 text-sky-300',
};
</script>
