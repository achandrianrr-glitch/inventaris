<script setup>
import { computed } from "vue";

const props = defineProps({
    labels: { type: Array, default: () => [] }, // ["2026-02-01", ...]
    datasets: {
        type: Array,
        default: () => [], // [{ label:'Barang Masuk', values:[...]}]
    },
    height: { type: Number, default: 220 },
});

const W = 900;
const H = computed(() => props.height);

const pad = 28;

const maxVal = computed(() => {
    let m = 0;
    for (const ds of props.datasets) {
        for (const v of ds.values || []) m = Math.max(m, Number(v || 0));
    }
    return Math.max(m, 1);
});

const plotW = computed(() => W - pad * 2);
const plotH = computed(() => H.value - pad * 2);

const colors = ["#1e4db7", "#ef4444", "#10b981", "#f59e0b"];

function points(values) {
    const n = Math.max(values.length, 1);
    const stepX = n === 1 ? 0 : plotW.value / (n - 1);

    return values
        .map((v, i) => {
            const x = pad + i * stepX;
            const y = pad + (maxVal.value - Number(v || 0)) * (plotH.value / maxVal.value);
            return `${x},${y}`;
        })
        .join(" ");
}

const gridY = computed(() => {
    const lines = 4;
    const arr = [];
    for (let i = 0; i <= lines; i++) {
        arr.push(pad + (plotH.value / lines) * i);
    }
    return arr;
});

const summary = computed(() => {
    const sum = (arr) => (arr || []).reduce((a, b) => a + Number(b || 0), 0);
    return props.datasets.map((ds) => ({ label: ds.label, total: sum(ds.values) }));
});
</script>

<template>
    <div class="chart-wrap">
        <svg :viewBox="`0 0 ${W} ${H}`" class="w-100">
            <!-- grid -->
            <g>
                <line v-for="(y, idx) in gridY" :key="idx" :x1="pad" :x2="W - pad" :y1="y" :y2="y"
                    stroke="rgba(2,6,23,.10)" stroke-width="1" />
            </g>

            <!-- lines -->
            <g v-for="(ds, idx) in datasets" :key="idx">
                <polyline :points="points(ds.values || [])" fill="none" :stroke="colors[idx % colors.length]"
                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
            </g>
        </svg>

        <!-- legend + total -->
        <div class="legend">
            <div v-for="(s, idx) in summary" :key="idx" class="legend-item">
                <span class="dot" :style="{ background: colors[idx % colors.length] }"></span>
                <div class="min-w-0">
                    <div class="small text-muted">{{ s.label }}</div>
                    <div class="fw-semibold">{{ s.total }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.chart-wrap {
    padding: 12px 14px 14px;
}

.legend {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid rgba(2, 6, 23, 0.08);
    background: rgba(255, 255, 255, 0.7);
    border-radius: 14px;
    padding: 10px;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 999px;
}

.min-w-0 {
    min-width: 0;
}

@media (max-width: 768px) {
    .legend {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
