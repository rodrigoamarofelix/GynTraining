<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition focus:outline-none focus:ring-2 focus:ring-emerald-500/50 disabled:cursor-not-allowed disabled:opacity-60"
        :class="[variantClasses[variant], sizeClasses[size]]"
    >
        <span
            v-if="loading"
            class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
        />
        <slot />
    </button>
</template>

<script setup>
defineProps({
    variant: {
        type: String,
        default: 'primary',
    },
    size: {
        type: String,
        default: 'md',
    },
    type: {
        type: String,
        default: 'button',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const variantClasses = {
    primary: 'bg-emerald-500 text-slate-950 hover:bg-emerald-400',
    secondary: 'bg-slate-800 text-slate-100 hover:bg-slate-700 border border-slate-700',
    ghost: 'bg-transparent text-slate-300 hover:bg-slate-800',
    danger: 'bg-red-500/90 text-white hover:bg-red-500',
};

const sizeClasses = {
    md: 'px-5 py-3 text-sm',
    lg: 'px-6 py-4 text-base w-full',
    sm: 'px-4 py-2 text-sm',
};
</script>
