<template>
    <label class="block space-y-2">
        <span v-if="label" class="text-sm font-medium text-slate-300">{{ label }}</span>
        <select
            :value="modelValue"
            :disabled="disabled"
            class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-sm text-white focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 disabled:cursor-not-allowed disabled:opacity-60"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-if="placeholder" value="">{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
        <p v-if="error" class="text-sm text-red-400">{{ error }}</p>
    </label>
</template>

<script setup>
defineProps({
    label: String,
    modelValue: [String, Number],
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: String,
    error: String,
    disabled: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);
</script>
