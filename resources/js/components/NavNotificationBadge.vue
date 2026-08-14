<template>
    <span
        v-if="count > 0"
        class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white shadow-sm"
        :class="floating ? 'absolute -right-2 -top-1.5' : 'ml-1.5'"
        :aria-label="`${count} notificações não lidas`"
    >
        {{ count > 99 ? '99+' : count }}
    </span>
</template>

<script setup>
defineProps({
    count: {
        type: Number,
        default: 0,
    },
    floating: {
        type: Boolean,
        default: false,
    },
});
</script>
