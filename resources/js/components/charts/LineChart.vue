<template>
    <div class="relative w-full" :style="{ height: `${height}px` }">
        <canvas ref="canvasRef" />
    </div>
</template>

<script setup>
import {
    CategoryScale,
    Chart,
    Filler,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { baseChartOptions } from '../../utils/chartTheme';

Chart.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend, Filler);

const props = defineProps({
    labels: {
        type: Array,
        default: () => [],
    },
    datasets: {
        type: Array,
        default: () => [],
    },
    height: {
        type: Number,
        default: 260,
    },
    ySuffix: {
        type: String,
        default: '',
    },
});

const canvasRef = ref(null);
let chartInstance = null;

function buildOptions() {
    const options = baseChartOptions();

    if (props.ySuffix) {
        options.scales.y.ticks.callback = (value) => `${value}${props.ySuffix}`;
        options.plugins.tooltip.callbacks = {
            label(context) {
                const label = context.dataset.label ?? '';
                const value = context.parsed.y;

                return `${label}: ${value}${props.ySuffix}`;
            },
        };
    }

    return options;
}

function renderChart() {
    if (! canvasRef.value) {
        return;
    }

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(canvasRef.value, {
        type: 'line',
        data: {
            labels: props.labels,
            datasets: props.datasets,
        },
        options: buildOptions(),
    });
}

watch(
    () => [props.labels, props.datasets, props.ySuffix],
    () => renderChart(),
    { deep: true },
);

onMounted(renderChart);

onBeforeUnmount(() => {
    chartInstance?.destroy();
    chartInstance = null;
});
</script>
