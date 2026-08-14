<template>
    <div
        v-if="active"
        class="fixed inset-x-0 bottom-24 z-40 mx-auto max-w-md px-4 md:bottom-8"
    >
        <div class="rounded-2xl border border-emerald-500/30 bg-slate-900 p-5 shadow-2xl shadow-emerald-500/10">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Descanso</p>
                    <p class="mt-1 text-4xl font-bold tabular-nums text-white">{{ formattedTime }}</p>
                </div>
                <UiButton variant="ghost" @click="skip">Pular</UiButton>
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-800">
                <div
                    class="h-full rounded-full bg-emerald-500 transition-all duration-1000 ease-linear"
                    :style="{ width: `${progress}%` }"
                />
            </div>
            <p v-if="finished" class="mt-3 text-center text-sm font-semibold text-emerald-300">
                Descanso finalizado!
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import UiButton from './ui/UiButton.vue';

const props = defineProps({
    seconds: {
        type: Number,
        default: 60,
    },
    active: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['finished', 'skip']);

const remaining = ref(0);
const finished = ref(false);
let intervalId = null;

const formattedTime = computed(() => {
    const mins = Math.floor(remaining.value / 60);
    const secs = remaining.value % 60;

    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const progress = computed(() => {
    if (! props.seconds) {
        return 0;
    }

    return ((props.seconds - remaining.value) / props.seconds) * 100;
});

function clearTimer() {
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
    }
}

function playAlert() {
    try {
        const context = new AudioContext();
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 880;
        gain.gain.value = 0.08;
        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start();
        setTimeout(() => {
            oscillator.stop();
            context.close();
        }, 250);
    } catch {
        // Audio not available
    }

    if ('vibrate' in navigator) {
        navigator.vibrate([200, 100, 200]);
    }
}

function startTimer() {
    clearTimer();
    remaining.value = props.seconds;
    finished.value = false;

    intervalId = setInterval(() => {
        if (remaining.value <= 1) {
            remaining.value = 0;
            finished.value = true;
            clearTimer();
            playAlert();
            emit('finished');

            return;
        }

        remaining.value -= 1;
    }, 1000);
}

function skip() {
    clearTimer();
    finished.value = false;
    emit('skip');
}

watch(
    () => [props.active, props.seconds],
    ([active]) => {
        if (active) {
            startTimer();
        } else {
            clearTimer();
            finished.value = false;
        }
    },
    { immediate: true },
);

onBeforeUnmount(clearTimer);
</script>
