<script setup lang="ts">
import {
  ArcElement,
  ChartData,
  Chart as ChartJS,
  ChartOptions,
  Legend,
  Title,
  Tooltip,
} from 'chart.js';
import { ref } from 'vue';
import { Doughnut } from 'vue-chartjs';

// Register Chart.js components
ChartJS.register(Title, Tooltip, Legend, ArcElement);

interface Props {
  data: { name: string; total: number; predicted?: number }[];
  category?: string;
  title?: string;
}

const props = defineProps<Props>();

const chartData = ref<ChartData<'doughnut'>>({
  labels: props.data.map((item) => item.name),
  datasets: [
    {
      label: props.category ?? 'Total',
      data: props.data.map((item) => item.total),
      backgroundColor: [
        '#3b82f6',
        '#22c55e',
        '#f59e0b',
        '#ef4444',
        '#a855f7',
        '#06b6d4',
      ],
      borderWidth: 2,
      borderColor: '#fff',
      hoverOffset: 10,
    },
  ],
});

const options = ref<ChartOptions<'doughnut'>>({
  responsive: true,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        font: { size: 12 },
      },
    },
    title: {
      display: !!props.title,
      text: props.title,
    },
  },
});
</script>

<template>
  <div class="mx-auto w-full max-w-md">
    <Doughnut :data="chartData" :options="options" />
  </div>
</template>
