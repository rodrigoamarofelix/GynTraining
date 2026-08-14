export const chartColors = {
    emerald: '#10b981',
    emeraldSoft: 'rgba(16, 185, 129, 0.15)',
    sky: '#38bdf8',
    skySoft: 'rgba(56, 189, 248, 0.15)',
    amber: '#fbbf24',
    amberSoft: 'rgba(251, 191, 36, 0.15)',
    slate: '#64748b',
    grid: '#1e293b',
    text: '#94a3b8',
};

export function baseChartOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                labels: {
                    color: chartColors.text,
                    boxWidth: 12,
                    usePointStyle: true,
                },
            },
            tooltip: {
                backgroundColor: '#0f172a',
                borderColor: '#334155',
                borderWidth: 1,
                titleColor: '#f8fafc',
                bodyColor: '#cbd5e1',
            },
        },
        scales: {
            x: {
                ticks: { color: chartColors.slate, maxRotation: 0 },
                grid: { color: chartColors.grid },
            },
            y: {
                ticks: { color: chartColors.slate },
                grid: { color: chartColors.grid },
                beginAtZero: false,
            },
        },
    };
}

export function lineDataset(label, data, color = chartColors.emerald) {
    const softMap = {
        [chartColors.emerald]: chartColors.emeraldSoft,
        [chartColors.sky]: chartColors.skySoft,
        [chartColors.amber]: chartColors.amberSoft,
    };

    return {
        label,
        data,
        borderColor: color,
        backgroundColor: softMap[color] ?? chartColors.emeraldSoft,
        tension: 0.35,
        fill: 'origin',
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: color,
        pointBorderColor: '#0f172a',
        pointBorderWidth: 2,
    };
}
