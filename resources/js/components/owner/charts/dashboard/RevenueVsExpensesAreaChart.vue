<script setup lang="ts">
import {
  CategoryScale,
  ChartData,
  Chart as ChartJS,
  ChartOptions,
  Filler,
  Legend,
  LinearScale,
  LineElement,
  PointElement,
  Title,
  Tooltip,
} from 'chart.js';
import { computed, ref } from 'vue';
import { Line } from 'vue-chartjs';

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
);

// Props
interface DataItem {
  date: string; // Use 'date' instead of 'name'
  Revenue: number;
  Expenses: number;
}

const props = defineProps<{
  data: DataItem[];
  categories?: string[]; // ['Expenses', 'Revenue']
  colors?: string[]; // ['#33cc66', '#005dcf']
}>();

const colors = props.colors ?? ['#3b82f6', '#22c55e']; // default colors
const categories = props.categories ?? ['Revenue', 'Expenses'];

// Chart Data
const chartData = computed<ChartData<'line'>>(() => ({
  labels: props.data.map((d) => d.date), // Use 'date' for X-axis
  datasets: categories.map((cat, i) => ({
    label: cat,
    data: props.data.map((d) => d[cat as keyof DataItem] as number),
    borderColor: colors[i] ?? colors[0],
    backgroundColor: (colors[i] ?? colors[0]) + '33', // semi-transparent fill
    tension: 0.4,
    fill: true,
    pointRadius: 4,
    pointHoverRadius: 6,
  })),
}));

// Chart Options
const chartOptions = ref<ChartOptions<'line'>>({
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index' as const,
    intersect: false,
  },
  plugins: {
    legend: {
      position: 'top',
      labels: { color: '#374151' },
    },
    title: {
      display: true,
      text: 'Revenue vs Expenses',
      color: '#111827',
      font: { size: 16, weight: 'bold' },
    },
    tooltip: {
      backgroundColor: '#111827',
      borderColor: '#e5e7eb',
      borderWidth: 1,
      titleColor: '#f9fafb',
      bodyColor: '#f9fafb',
      callbacks: {
        label: (context) => {
          const label = context.dataset.label || '';
          const value = context.parsed.y;
          if (value === null) return `${label}: N/A`;
          return `${label}: ₱${value.toLocaleString()}`;
        },
      },
    },
  },
  scales: {
    x: {
      ticks: { color: '#6b7280' },
      grid: { display: false },
    },
    y: {
      beginAtZero: true,
      ticks: {
        color: '#6b7280',
        callback: (val) => `₱${Number(val).toLocaleString()}`,
      },
      grid: { color: '#e5e7eb' },
    },
  },
});
</script>

<template>
  <div class="h-[400px] w-full">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
