<script setup lang="ts">
import {
  CategoryScale,
  ChartData,
  Chart as ChartJS,
  ChartOptions,
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
);

interface DataItem {
  year: number;
  'Growth Rate': number;
}

const props = defineProps<{
  data: DataItem[];
  colors?: string[];
  yFormatter?: (value: number) => string;
}>();

// Default color
const chartColor = props.colors?.[0] ?? '#3b82f6'; // Tailwind blue-500

// Format numbers (fallback)
const formatY = props.yFormatter ?? ((val: number) => `$ ${val.toFixed(2)}`);

const chartData = computed<ChartData<'line'>>(() => ({
  labels: props.data.map((item) => item.year),
  datasets: [
    {
      label: 'Growth Rate',
      data: props.data.map((item) => item['Growth Rate']),
      borderColor: chartColor,
      backgroundColor: chartColor + '33',
      tension: 0.3,
      fill: true,
      pointRadius: 4,
      pointHoverRadius: 6,
    },
  ],
}));

const chartOptions = ref<ChartOptions<'line'>>({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top',
      labels: { color: '#374151' },
    },
    tooltip: {
      backgroundColor: '#111827',
      titleColor: '#f9fafb',
      bodyColor: '#f9fafb',
      borderColor: '#3b82f6',
      borderWidth: 1,
      callbacks: {
        label: (context) => {
          const value = context.parsed.y;
          if (value === null) return 'Growth Rate: N/A';
          return `Growth Rate: ${formatY(value)}`;
        },
      },
    },
    // title: {
    //   display: true,
    //   text: 'Growth Rate Over Years',
    //   color: '#111827',
    //   font: { size: 16, weight: 'bold' },
    // },
  },
  scales: {
    x: {
      ticks: { color: '#6b7280' },
      grid: { display: false },
    },
    y: {
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
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>
