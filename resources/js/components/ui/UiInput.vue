<template>
    <label class="block space-y-2">
        <span v-if="label" class="text-sm font-medium text-slate-300">{{ label }}</span>
        <input
            v-bind="$attrs"
            :value="modelValue"
            :disabled="disabled"
            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 disabled:cursor-not-allowed disabled:opacity-60"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <p v-if="error" class="text-sm text-red-400">{{ error }}</p>
    </label>
</template>

<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    label: String,
    modelValue: [String, Number],
    error: String,
    disabled: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);
</script>
