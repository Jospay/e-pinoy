<script setup lang="ts">
import {
  BarElement,
  CategoryScale,
  ChartData,
  Chart as ChartJS,
  ChartOptions,
  Legend,
  LinearScale,
  Title,
  Tooltip,
} from 'chart.js';
import { computed, ref } from 'vue';
import { Bar } from 'vue-chartjs';

// Register Chart.js modules
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
);

interface DataItem {
  name: string;
  expenses: number;
  revenue: number;
}

const props = defineProps<{
  data: DataItem[];
  categories?: string[]; // ['expenses', 'revenue']
  colors?: string[]; // ['red', 'green']
  yFormatter?: (value: number) => string;
}>();

// Default colors and categories
const colors = props.colors ?? ['#ef4444', '#22c55e'];
const categories = props.categories ?? ['expenses', 'revenue'];

// Default y-axis formatter
const formatY = props.yFormatter ?? ((v: number) => `$${v.toLocaleString()}`);

// Prepare Chart.js data
const chartData = computed<ChartData<'bar'>>(() => ({
  labels: props.data.map((d) => d.name),
  datasets: categories.map((cat, i) => ({
    label: cat.charAt(0).toUpperCase() + cat.slice(1),
    data: props.data.map((d) => d[cat as keyof DataItem] as number),
    backgroundColor: colors[i] ?? colors[0],
    borderRadius: 6,
    barThickness: 28,
  })),
}));

// Chart options
const chartOptions = ref<ChartOptions<'bar'>>({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        color: '#374151',
        font: { size: 12 },
      },
    },
    title: {
      display: true,
      text: 'Revenue vs Expenses',
      color: '#111827',
      font: { size: 16, weight: 'bold' },
    },
    tooltip: {
      backgroundColor: '#111827',
      titleColor: '#f9fafb',
      bodyColor: '#f9fafb',
      borderColor: '#e5e7eb',
      borderWidth: 1,
      callbacks: {
        label: (context) => {
          const label = context.dataset.label || '';
          const value = context.parsed.y;
          if (value === null) return `${label}: N/A`;
          return `${label}: ${formatY(value)}`;
        },
      },
    },
  },
  interaction: {
    mode: 'index' as const,
    intersect: false,
  },
  scales: {
    x: {
      ticks: { color: '#6b7280' },
      grid: { display: false },
      stacked: false,
    },
    y: {
      beginAtZero: true,
      ticks: {
        color: '#6b7280',
        callback: (val) => formatY(Number(val)),
      },
      grid: { color: '#e5e7eb' },
    },
  },
});
</script>

<template>
  <div class="h-[400px] w-full">
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>
