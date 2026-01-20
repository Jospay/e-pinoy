<script setup lang="ts">
import { DonutChart } from '@/components/ui/chart-donut';

const data = [
  {
    name: 'Trip',
    total: Math.floor(Math.random() * 2000) + 500,
    predicted: Math.floor(Math.random() * 2000) + 500,
  },
  {
    name: 'Ads',
    total: Math.floor(Math.random() * 2000) + 500,
    predicted: Math.floor(Math.random() * 2000) + 500,
  },
  {
    name: 'Logistics',
    total: Math.floor(Math.random() * 2000) + 500,
    predicted: Math.floor(Math.random() * 2000) + 500,
  },
];
</script>

<template>
  <DonutChart
    index="name"
    :colors="['red', 'blue', 'green']"
    :category="'total'"
    :data="data"
    :type="'pie'"
  />
</template>
