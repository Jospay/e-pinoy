<script setup lang="ts">
import CustomChartTooltip from '@/components/finance/CustomChartTooltip.vue';
import { LineChart } from '@/components/ui/chart-line';

const data = [
  { year: 2000, 'Growth Rate': 2.07 },
  { year: 2001, 'Growth Rate': 2.08 },
  { year: 2002, 'Growth Rate': 2.1 },
  { year: 2003, 'Growth Rate': 2.15 },
  { year: 2004, 'Growth Rate': 2.21 },
  { year: 2005, 'Growth Rate': 2.23 },
  { year: 2006, 'Growth Rate': 2.29 },
  { year: 2007, 'Growth Rate': 2.34 },
  { year: 2008, 'Growth Rate': 2.36 },
  { year: 2009, 'Growth Rate': 2.36 },
  { year: 2010, 'Growth Rate': 2.35 },
  { year: 2011, 'Growth Rate': 2.34 },
  { year: 2012, 'Growth Rate': 2.39 },
  { year: 2013, 'Growth Rate': 2.3 },
  { year: 2014, 'Growth Rate': 2.35 },
  { year: 2015, 'Growth Rate': 2.39 },
  { year: 2016, 'Growth Rate': 2.41 },
  { year: 2017, 'Growth Rate': 2.44 },
  { year: 2018, 'Growth Rate': 2.45 },
  { year: 2019, 'Growth Rate': 2.47 },
  { year: 2020, 'Growth Rate': 2.48 },
  { year: 2021, 'Growth Rate': 2.51 },
  { year: 2022, 'Growth Rate': 2.55 },
  { year: 2023, 'Growth Rate': 2.58 },
  { year: 2024, 'Growth Rate': 2.6 },
  { year: 2025, 'Growth Rate': 2.63 },
];
</script>

<template>
  <LineChart
    index="year"
    :colors="['blue']"
    :data="data"
    :categories="['Growth Rate']"
    :y-formatter="
      (tick, i) => {
        return typeof tick === 'number'
          ? `$ ${new Intl.NumberFormat('us').format(tick).toString()}`
          : '';
      }
    "
    :custom-tooltip="CustomChartTooltip"
  />
</template>
